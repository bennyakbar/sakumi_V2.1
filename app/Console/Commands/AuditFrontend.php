<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Frontend integrity auditor.
 *
 * Scans Blade views for:
 * - Forms missing @csrf on POST/PUT/PATCH/DELETE
 * - Forms with GET action pointing to POST routes (method mismatch)
 * - Dead links (href="#", javascript:void)
 * - Buttons without event binding (no onclick, no form, no wire:click, no x-on:click)
 * - Disabled elements without conditional logic
 * - Inline AJAX calls missing error handling
 */
class AuditFrontend extends Command
{
    protected $signature = 'audit:frontend
        {--json : Output results as JSON}
        {--severity=all : Filter by severity: critical, warning, info, all}';

    protected $description = 'Audit Blade views for frontend integrity issues';

    private array $issues = [];
    private int $filesScanned = 0;

    public function handle(): int
    {
        $this->info('');
        $this->info('  SAKUMI Frontend Integrity Audit');
        $this->info('  ═══════════════════════════════');
        $this->info('');

        $viewPath = resource_path('views');
        $files = File::allFiles($viewPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->filesScanned++;
            $relativePath = str_replace(resource_path() . '/', '', $file->getRealPath());
            $content = file_get_contents($file->getRealPath());
            $lines = file($file->getRealPath());

            $this->checkCsrfTokens($relativePath, $content, $lines);
            $this->checkDeadLinks($relativePath, $content, $lines);
            $this->checkOrphanButtons($relativePath, $content, $lines);
            $this->checkDisabledElements($relativePath, $content, $lines);
            $this->checkAjaxErrorHandling($relativePath, $content, $lines);
            $this->checkMethodMismatch($relativePath, $content, $lines);
        }

        return $this->renderResults();
    }

    /**
     * Check 1: POST/PUT/PATCH/DELETE forms missing @csrf.
     */
    private function checkCsrfTokens(string $file, string $content, array $lines): void
    {
        // Find all <form> blocks and check for @csrf
        if (preg_match_all('/<form\b[^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$formTag, $offset]) {
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

                // GET forms don't need CSRF
                if (preg_match('/method\s*=\s*["\']get["\']/i', $formTag)) {
                    continue;
                }

                // Find the closing </form> and check for @csrf between them
                $afterForm = substr($content, $offset);
                $closingPos = stripos($afterForm, '</form>');

                if ($closingPos === false) {
                    $this->addIssue($file, $lineNum, 'warning', 'Unclosed <form> tag', 'Add matching </form>');
                    continue;
                }

                $formBody = substr($afterForm, 0, $closingPos);

                if (! str_contains($formBody, '@csrf') && ! str_contains($formBody, 'csrf_field()')) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'critical',
                        'POST/PUT/DELETE form missing @csrf token',
                        'Add @csrf immediately after the opening <form> tag'
                    );
                }
            }
        }
    }

    /**
     * Check 2: Dead links — href="#", href="javascript:void(0)", empty href.
     */
    private function checkDeadLinks(string $file, string $content, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;

            // href="#" (but skip Alpine.js anchor patterns with @click)
            if (preg_match('/href\s*=\s*["\']#["\']/i', $line)
                && ! preg_match('/@click|x-on:click|x-on:click\.prevent|wire:click/i', $line)) {
                $this->addIssue(
                    $file,
                    $lineNum,
                    'warning',
                    'Dead link: href="#" without JavaScript handler on same element',
                    'Replace with a proper route, button element, or add @click handler'
                );
            }

            // javascript:void(0)
            if (preg_match('/href\s*=\s*["\']javascript:\s*void\s*\(\s*0\s*\)/i', $line)) {
                $this->addIssue(
                    $file,
                    $lineNum,
                    'warning',
                    'Dead link: href="javascript:void(0)"',
                    'Use a <button> element instead of an anchor with JavaScript href'
                );
            }

            // Empty href
            if (preg_match('/href\s*=\s*["\']\s*["\']/i', $line)
                && ! preg_match('/href\s*=\s*["\']\s*{/i', $line)) {
                $this->addIssue(
                    $file,
                    $lineNum,
                    'warning',
                    'Empty href attribute',
                    'Add a valid URL or use a <button> element'
                );
            }
        }
    }

    /**
     * Check 3: Buttons that appear to have no event binding.
     */
    private function checkOrphanButtons(string $file, string $content, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;

            // Match <button elements that are NOT type="submit" and NOT type="reset"
            if (preg_match('/<button\b/i', $line)
                && preg_match('/type\s*=\s*["\']button["\']/i', $line)) {

                $hasHandler = preg_match(
                    '/onclick|@click|x-on:click|wire:click|x-on:submit|hx-post|hx-get|hx-delete|alpine/i',
                    $line
                );

                // Also check if it has an ID that might be bound via addEventListener
                $hasId = preg_match('/\bid\s*=\s*["\']/i', $line);

                if (! $hasHandler && ! $hasId) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'warning',
                        'Button type="button" without visible event handler',
                        'Add onclick, @click, or wire:click — or use type="submit" within a form'
                    );
                }
            }
        }
    }

    /**
     * Check 4: Disabled elements without conditional/dynamic logic.
     */
    private function checkDisabledElements(string $file, string $content, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;

            // Static "disabled" attribute without Blade conditional or Alpine binding
            if (preg_match('/\bdisabled\b/i', $line)
                && ! preg_match('/:disabled|x-bind:disabled|\@if.*disabled|disabled.*\?\s|disabled\s*=\s*["\']?\s*\{\{/i', $line)
                && ! preg_match('/x-bind|:class.*disabled/i', $line)) {

                // Skip if inside a Blade @if/@unless block (check surrounding lines)
                $contextStart = max(0, $i - 3);
                $context = implode('', array_slice($lines, $contextStart, 6));

                if (preg_match('/@if|@unless|@can|@when|x-show|x-if/i', $context)) {
                    continue;
                }

                $this->addIssue(
                    $file,
                    $lineNum,
                    'info',
                    'Element has static "disabled" attribute without conditional logic',
                    'If this should be conditionally enabled, use :disabled="condition" or @if/@unless'
                );
            }
        }
    }

    /**
     * Check 5: AJAX/fetch calls without error handling.
     */
    private function checkAjaxErrorHandling(string $file, string $content, array $lines): void
    {
        // Find fetch() blocks
        if (preg_match_all('/\bfetch\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$match, $offset]) {
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check for .catch or try/catch within a reasonable range
                $surrounding = substr($content, $offset, 500);

                $hasCatch = str_contains($surrounding, '.catch')
                    || str_contains($surrounding, 'catch (')
                    || str_contains($surrounding, 'catch(');

                if (! $hasCatch) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'critical',
                        'fetch() call without .catch() or try/catch error handling',
                        'Add .catch(err => { /* handle */ }) or wrap in try/catch with user-facing error message'
                    );
                }
            }
        }

        // Find axios calls
        if (preg_match_all('/\baxios\.\w+\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$match, $offset]) {
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
                $surrounding = substr($content, $offset, 500);

                $hasCatch = str_contains($surrounding, '.catch')
                    || str_contains($surrounding, 'catch (')
                    || str_contains($surrounding, 'catch(');

                if (! $hasCatch) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'critical',
                        'axios call without .catch() error handling',
                        'Add .catch(err => { /* handle */ }) with user-facing error feedback'
                    );
                }
            }
        }
    }

    /**
     * Check 6: Forms with method that may mismatch the route definition.
     */
    private function checkMethodMismatch(string $file, string $content, array $lines): void
    {
        // Find forms with explicit method="GET" that post to store/update/destroy routes
        if (preg_match_all('/<form\b[^>]*method\s*=\s*["\']get["\'][^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$formTag, $offset]) {
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check if action points to a write route name
                if (preg_match('/action\s*=\s*["\'][^"\']*\b(store|update|destroy|delete|create|save)\b/i', $formTag)) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'critical',
                        'GET form action points to a write endpoint (store/update/destroy)',
                        'Change method to POST and add @csrf, or change the route'
                    );
                }
            }
        }

        // Find POST forms with @method that references an unexpected verb
        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;

            if (preg_match("/@method\s*\(\s*['\"](\w+)['\"]\s*\)/i", $line, $methodMatch)) {
                $verb = strtoupper($methodMatch[1]);
                if (! in_array($verb, ['PUT', 'PATCH', 'DELETE'], true)) {
                    $this->addIssue(
                        $file,
                        $lineNum,
                        'warning',
                        "Unusual @method('{$verb}') — expected PUT, PATCH, or DELETE",
                        'Verify the HTTP verb matches the route definition'
                    );
                }
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────

    private function addIssue(string $file, int $line, string $severity, string $message, string $fix): void
    {
        $this->issues[] = [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'message' => $message,
            'fix' => $fix,
        ];
    }

    private function renderResults(): int
    {
        $severityFilter = $this->option('severity');

        $filtered = $severityFilter === 'all'
            ? $this->issues
            : array_filter($this->issues, fn ($i) => $i['severity'] === $severityFilter);

        if ($this->option('json')) {
            $this->line(json_encode([
                'files_scanned' => $this->filesScanned,
                'issues' => array_values($filtered),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return count(array_filter($filtered, fn ($i) => $i['severity'] === 'critical')) > 0 ? 1 : 0;
        }

        $grouped = collect($filtered)->groupBy('severity');

        $severityOrder = ['critical', 'warning', 'info'];
        $severityLabels = [
            'critical' => '<fg=red;options=bold>CRITICAL</>',
            'warning' => '<fg=yellow;options=bold>WARNING</>',
            'info' => '<fg=blue>INFO</>',
        ];

        foreach ($severityOrder as $level) {
            if (! $grouped->has($level)) {
                continue;
            }

            $items = $grouped->get($level);
            $this->line("  {$severityLabels[$level]} ({$items->count()})");
            $this->line('  ' . str_repeat('─', 60));

            foreach ($items as $issue) {
                $this->line("    <fg=cyan>{$issue['file']}:{$issue['line']}</>");
                $this->line("    {$issue['message']}");
                $this->line("    <fg=green>Fix:</> {$issue['fix']}");
                $this->line('');
            }
        }

        $criticals = $grouped->get('critical', collect())->count();
        $warnings = $grouped->get('warning', collect())->count();
        $infos = $grouped->get('info', collect())->count();

        $this->info("  Summary: {$this->filesScanned} Blade files scanned");
        $this->line("  <fg=red>{$criticals} critical</> | <fg=yellow>{$warnings} warnings</> | <fg=blue>{$infos} info</>");
        $this->info('');

        if ($criticals > 0) {
            $this->error('  AUDIT FAILED — critical frontend issues detected.');
            return 1;
        }

        $this->info('  AUDIT PASSED.');
        return 0;
    }
}
