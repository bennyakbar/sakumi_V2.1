--
-- PostgreSQL database dump
--

\restrict gbOAZVt3ekZC6GXoXVoNMiQDQvHKgFyLcEAWi9Vp7u18tFjirIc5Yrg8sxO24Z4

-- Dumped from database version 14.20 (Ubuntu 14.20-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 14.20 (Ubuntu 14.20-0ubuntu0.22.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: prevent_invoice_over_settlement(); Type: FUNCTION; Schema: public; Owner: sakumi_user
--

CREATE FUNCTION public.prevent_invoice_over_settlement() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            DECLARE
                v_invoice_total   NUMERIC(15,2);
                v_settled_sum     NUMERIC(15,2);
                v_settlement_status TEXT;
            BEGIN
                -- On settlement_allocations INSERT/UPDATE, check the linked invoice
                -- Only enforce when the parent settlement is 'completed'
                SELECT s.status INTO v_settlement_status
                FROM settlements s
                WHERE s.id = NEW.settlement_id;

                IF v_settlement_status != 'completed' THEN
                    RETURN NEW;
                END IF;

                SELECT total_amount INTO v_invoice_total
                FROM invoices
                WHERE id = NEW.invoice_id;

                SELECT COALESCE(SUM(sa.amount), 0) INTO v_settled_sum
                FROM settlement_allocations sa
                JOIN settlements s ON s.id = sa.settlement_id AND s.status = 'completed'
                WHERE sa.invoice_id = NEW.invoice_id
                  AND sa.id IS DISTINCT FROM NEW.id;

                -- Add the new/updated allocation amount
                v_settled_sum := v_settled_sum + NEW.amount;

                IF v_settled_sum > v_invoice_total THEN
                    RAISE EXCEPTION 'Over-settlement blocked: invoice % total is % but settled sum would be %',
                        NEW.invoice_id, v_invoice_total, v_settled_sum;
                END IF;

                RETURN NEW;
            END;
            $$;


ALTER FUNCTION public.prevent_invoice_over_settlement() OWNER TO sakumi_user;

--
-- Name: prevent_invoice_update(); Type: FUNCTION; Schema: public; Owner: sakumi_user
--

CREATE FUNCTION public.prevent_invoice_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                IF OLD.status IN ('unpaid', 'partially_paid', 'paid')
                   AND NEW.status IN ('unpaid', 'partially_paid', 'paid') THEN
                    IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                       OR OLD.invoice_number IS DISTINCT FROM NEW.invoice_number
                       OR OLD.student_id IS DISTINCT FROM NEW.student_id
                       OR OLD.invoice_date IS DISTINCT FROM NEW.invoice_date
                       OR OLD.period_type IS DISTINCT FROM NEW.period_type
                       OR OLD.period_identifier IS DISTINCT FROM NEW.period_identifier THEN
                        RAISE EXCEPTION 'Cannot modify immutable fields on active invoices';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$;


ALTER FUNCTION public.prevent_invoice_update() OWNER TO sakumi_user;

--
-- Name: prevent_settlement_update(); Type: FUNCTION; Schema: public; Owner: sakumi_user
--

CREATE FUNCTION public.prevent_settlement_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
                    IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                       OR OLD.settlement_number IS DISTINCT FROM NEW.settlement_number
                       OR OLD.student_id IS DISTINCT FROM NEW.student_id
                       OR OLD.payment_date IS DISTINCT FROM NEW.payment_date
                       OR OLD.payment_method IS DISTINCT FROM NEW.payment_method
                       OR OLD.allocated_amount IS DISTINCT FROM NEW.allocated_amount THEN
                        RAISE EXCEPTION 'Cannot modify immutable fields on completed settlements';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$;


ALTER FUNCTION public.prevent_settlement_update() OWNER TO sakumi_user;

--
-- Name: prevent_transaction_update(); Type: FUNCTION; Schema: public; Owner: sakumi_user
--

CREATE FUNCTION public.prevent_transaction_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
                BEGIN
                    IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
                        IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                           OR OLD.transaction_date IS DISTINCT FROM NEW.transaction_date
                           OR OLD.student_id IS DISTINCT FROM NEW.student_id
                           OR OLD.transaction_number IS DISTINCT FROM NEW.transaction_number
                           OR OLD.type IS DISTINCT FROM NEW.type
                           OR OLD.description IS DISTINCT FROM NEW.description THEN
                            RAISE EXCEPTION 'Cannot modify completed transactions';
                        END IF;
                    END IF;
                    RETURN NEW;
                END;
                $$;


ALTER FUNCTION public.prevent_transaction_update() OWNER TO sakumi_user;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: academic_years; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.academic_years (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    code character varying(9) NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    is_active boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT academic_years_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'active'::character varying, 'closed'::character varying])::text[])))
);


ALTER TABLE public.academic_years OWNER TO sakumi_user;

--
-- Name: academic_years_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.academic_years_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.academic_years_id_seq OWNER TO sakumi_user;

--
-- Name: academic_years_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.academic_years_id_seq OWNED BY public.academic_years.id;


--
-- Name: account_mappings; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.account_mappings (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    event_type character varying(60) NOT NULL,
    line_key character varying(60) NOT NULL,
    entry_side character varying(10) NOT NULL,
    account_code character varying(30) NOT NULL,
    priority smallint DEFAULT '100'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    description character varying(255),
    filters json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.account_mappings OWNER TO sakumi_user;

--
-- Name: account_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.account_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.account_mappings_id_seq OWNER TO sakumi_user;

--
-- Name: account_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.account_mappings_id_seq OWNED BY public.account_mappings.id;


--
-- Name: accounting_events; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.accounting_events (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    event_uuid uuid NOT NULL,
    event_type character varying(60) NOT NULL,
    source_type character varying(100),
    source_id bigint,
    idempotency_key character varying(191),
    effective_date date NOT NULL,
    occurred_at timestamp(0) without time zone NOT NULL,
    fiscal_period_id bigint,
    is_reversal boolean DEFAULT false NOT NULL,
    reversal_of_event_id bigint,
    status character varying(20) DEFAULT 'posted'::character varying NOT NULL,
    created_by bigint,
    payload json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.accounting_events OWNER TO sakumi_user;

--
-- Name: accounting_events_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.accounting_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.accounting_events_id_seq OWNER TO sakumi_user;

--
-- Name: accounting_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.accounting_events_id_seq OWNED BY public.accounting_events.id;


--
-- Name: accounts; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.accounts (
    id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(120) NOT NULL,
    type character varying(20) NOT NULL,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL
);


ALTER TABLE public.accounts OWNER TO sakumi_user;

--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.accounts_id_seq OWNER TO sakumi_user;

--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.accounts_id_seq OWNED BY public.accounts.id;


--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


ALTER TABLE public.activity_log OWNER TO sakumi_user;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.activity_log_id_seq OWNER TO sakumi_user;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: admission_period_quotas; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.admission_period_quotas (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    admission_period_id bigint NOT NULL,
    class_id bigint NOT NULL,
    quota integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.admission_period_quotas OWNER TO sakumi_user;

--
-- Name: admission_period_quotas_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.admission_period_quotas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.admission_period_quotas_id_seq OWNER TO sakumi_user;

--
-- Name: admission_period_quotas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.admission_period_quotas_id_seq OWNED BY public.admission_period_quotas.id;


--
-- Name: admission_periods; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.admission_periods (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    academic_year character varying(20) NOT NULL,
    registration_open date NOT NULL,
    registration_close date NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT chk_admission_periods_status CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'open'::character varying, 'closed'::character varying])::text[])))
);


ALTER TABLE public.admission_periods OWNER TO sakumi_user;

--
-- Name: admission_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.admission_periods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.admission_periods_id_seq OWNER TO sakumi_user;

--
-- Name: admission_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.admission_periods_id_seq OWNED BY public.admission_periods.id;


--
-- Name: applicants; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.applicants (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    admission_period_id bigint NOT NULL,
    registration_number character varying(30) NOT NULL,
    name character varying(255) NOT NULL,
    target_class_id bigint NOT NULL,
    category_id bigint NOT NULL,
    gender character(1) NOT NULL,
    birth_date date,
    birth_place character varying(100),
    parent_name character varying(255),
    parent_phone character varying(20),
    parent_whatsapp character varying(20),
    address text,
    previous_school character varying(255),
    status character varying(20) DEFAULT 'registered'::character varying NOT NULL,
    rejection_reason text,
    status_changed_at date,
    status_changed_by bigint,
    student_id bigint,
    created_by bigint NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT chk_applicants_gender CHECK ((gender = ANY (ARRAY['L'::bpchar, 'P'::bpchar]))),
    CONSTRAINT chk_applicants_status CHECK (((status)::text = ANY ((ARRAY['registered'::character varying, 'under_review'::character varying, 'accepted'::character varying, 'rejected'::character varying, 'enrolled'::character varying])::text[])))
);


ALTER TABLE public.applicants OWNER TO sakumi_user;

--
-- Name: applicants_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.applicants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.applicants_id_seq OWNER TO sakumi_user;

--
-- Name: applicants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.applicants_id_seq OWNED BY public.applicants.id;


--
-- Name: bank_reconciliation_lines; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.bank_reconciliation_lines (
    id bigint NOT NULL,
    bank_reconciliation_session_id bigint NOT NULL,
    line_date date NOT NULL,
    description character varying(255),
    reference character varying(120),
    amount numeric(15,2) NOT NULL,
    type character varying(20) DEFAULT 'debit'::character varying NOT NULL,
    match_status character varying(20) DEFAULT 'unmatched'::character varying NOT NULL,
    matched_transaction_id bigint,
    matched_by bigint,
    matched_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_bank_recon_line_match_status CHECK (((match_status)::text = ANY ((ARRAY['matched'::character varying, 'unmatched'::character varying, 'adjusted'::character varying])::text[]))),
    CONSTRAINT chk_bank_recon_line_type CHECK (((type)::text = ANY ((ARRAY['debit'::character varying, 'credit'::character varying])::text[])))
);


ALTER TABLE public.bank_reconciliation_lines OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_lines_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.bank_reconciliation_lines_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bank_reconciliation_lines_id_seq OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_lines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.bank_reconciliation_lines_id_seq OWNED BY public.bank_reconciliation_lines.id;


--
-- Name: bank_reconciliation_logs; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.bank_reconciliation_logs (
    id bigint NOT NULL,
    bank_reconciliation_session_id bigint NOT NULL,
    action character varying(60) NOT NULL,
    payload json,
    actor_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.bank_reconciliation_logs OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.bank_reconciliation_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bank_reconciliation_logs_id_seq OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.bank_reconciliation_logs_id_seq OWNED BY public.bank_reconciliation_logs.id;


--
-- Name: bank_reconciliation_sessions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.bank_reconciliation_sessions (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    bank_account_name character varying(150) NOT NULL,
    period_year smallint NOT NULL,
    period_month smallint NOT NULL,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    notes text,
    created_by bigint,
    updated_by bigint,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_bank_recon_session_month CHECK (((period_month >= 1) AND (period_month <= 12))),
    CONSTRAINT chk_bank_recon_session_status CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'in_review'::character varying, 'closed'::character varying])::text[])))
);


ALTER TABLE public.bank_reconciliation_sessions OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.bank_reconciliation_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.bank_reconciliation_sessions_id_seq OWNER TO sakumi_user;

--
-- Name: bank_reconciliation_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.bank_reconciliation_sessions_id_seq OWNED BY public.bank_reconciliation_sessions.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO sakumi_user;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO sakumi_user;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(120) NOT NULL,
    type character varying(20) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL
);


ALTER TABLE public.categories OWNER TO sakumi_user;

--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.categories_id_seq OWNER TO sakumi_user;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: chart_of_accounts; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.chart_of_accounts (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(120) NOT NULL,
    type character varying(20) NOT NULL,
    normal_balance character varying(10) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    parent_id bigint,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.chart_of_accounts OWNER TO sakumi_user;

--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.chart_of_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.chart_of_accounts_id_seq OWNER TO sakumi_user;

--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.chart_of_accounts_id_seq OWNED BY public.chart_of_accounts.id;


--
-- Name: class_promotion_paths; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.class_promotion_paths (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    from_class_id bigint NOT NULL,
    to_class_id bigint NOT NULL,
    from_academic_year_id bigint NOT NULL,
    to_academic_year_id bigint NOT NULL,
    priority smallint DEFAULT '100'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.class_promotion_paths OWNER TO sakumi_user;

--
-- Name: class_promotion_paths_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.class_promotion_paths_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.class_promotion_paths_id_seq OWNER TO sakumi_user;

--
-- Name: class_promotion_paths_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.class_promotion_paths_id_seq OWNED BY public.class_promotion_paths.id;


--
-- Name: classes; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.classes (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    level smallint NOT NULL,
    academic_year character varying(9) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    academic_year_id bigint
);


ALTER TABLE public.classes OWNER TO sakumi_user;

--
-- Name: classes_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.classes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.classes_id_seq OWNER TO sakumi_user;

--
-- Name: classes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.classes_id_seq OWNED BY public.classes.id;


--
-- Name: document_sequences; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.document_sequences (
    id bigint NOT NULL,
    prefix character varying(30) NOT NULL,
    last_sequence bigint DEFAULT '0'::bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.document_sequences OWNER TO sakumi_user;

--
-- Name: COLUMN document_sequences.prefix; Type: COMMENT; Schema: public; Owner: sakumi_user
--

COMMENT ON COLUMN public.document_sequences.prefix IS 'e.g. NF-2026, NK-2026, INV-MI-2026, STL-2026';


--
-- Name: document_sequences_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.document_sequences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.document_sequences_id_seq OWNER TO sakumi_user;

--
-- Name: document_sequences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.document_sequences_id_seq OWNED BY public.document_sequences.id;


--
-- Name: expense_budgets; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.expense_budgets (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    year smallint NOT NULL,
    month smallint NOT NULL,
    expense_fee_subcategory_id bigint NOT NULL,
    budget_amount numeric(15,2) NOT NULL,
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_expense_budget_month CHECK (((month >= 1) AND (month <= 12)))
);


ALTER TABLE public.expense_budgets OWNER TO sakumi_user;

--
-- Name: expense_budgets_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.expense_budgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.expense_budgets_id_seq OWNER TO sakumi_user;

--
-- Name: expense_budgets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.expense_budgets_id_seq OWNED BY public.expense_budgets.id;


--
-- Name: expense_entries; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.expense_entries (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    expense_fee_subcategory_id bigint NOT NULL,
    fee_type_id bigint NOT NULL,
    entry_date date NOT NULL,
    payment_method character varying(20) DEFAULT 'cash'::character varying NOT NULL,
    vendor_name character varying(150),
    amount numeric(15,2) NOT NULL,
    description text,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    posted_transaction_id bigint,
    approved_by bigint,
    approved_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_expense_entries_payment_method CHECK (((payment_method)::text = ANY ((ARRAY['cash'::character varying, 'transfer'::character varying, 'qris'::character varying])::text[]))),
    CONSTRAINT chk_expense_entries_status CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'approved'::character varying, 'posted'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.expense_entries OWNER TO sakumi_user;

--
-- Name: expense_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.expense_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.expense_entries_id_seq OWNER TO sakumi_user;

--
-- Name: expense_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.expense_entries_id_seq OWNED BY public.expense_entries.id;


--
-- Name: expense_fee_categories; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.expense_fee_categories (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(120) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.expense_fee_categories OWNER TO sakumi_user;

--
-- Name: expense_fee_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.expense_fee_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.expense_fee_categories_id_seq OWNER TO sakumi_user;

--
-- Name: expense_fee_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.expense_fee_categories_id_seq OWNED BY public.expense_fee_categories.id;


--
-- Name: expense_fee_subcategories; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.expense_fee_subcategories (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    expense_fee_category_id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(120) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.expense_fee_subcategories OWNER TO sakumi_user;

--
-- Name: expense_fee_subcategories_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.expense_fee_subcategories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.expense_fee_subcategories_id_seq OWNER TO sakumi_user;

--
-- Name: expense_fee_subcategories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.expense_fee_subcategories_id_seq OWNED BY public.expense_fee_subcategories.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO sakumi_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO sakumi_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fee_matrix; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.fee_matrix (
    id bigint NOT NULL,
    fee_type_id bigint NOT NULL,
    class_id bigint,
    category_id bigint,
    amount numeric(15,2) NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    is_active boolean DEFAULT true NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT chk_effective_dates CHECK (((effective_to IS NULL) OR (effective_to >= effective_from)))
);


ALTER TABLE public.fee_matrix OWNER TO sakumi_user;

--
-- Name: fee_matrix_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.fee_matrix_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fee_matrix_id_seq OWNER TO sakumi_user;

--
-- Name: fee_matrix_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.fee_matrix_id_seq OWNED BY public.fee_matrix.id;


--
-- Name: fee_types; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.fee_types (
    id bigint NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_monthly boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    expense_fee_subcategory_id bigint,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.fee_types OWNER TO sakumi_user;

--
-- Name: fee_types_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.fee_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fee_types_id_seq OWNER TO sakumi_user;

--
-- Name: fee_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.fee_types_id_seq OWNED BY public.fee_types.id;


--
-- Name: fiscal_periods; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.fiscal_periods (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    period_key character varying(20) NOT NULL,
    starts_on date NOT NULL,
    ends_on date NOT NULL,
    is_locked boolean DEFAULT false NOT NULL,
    locked_at timestamp(0) without time zone,
    locked_by bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.fiscal_periods OWNER TO sakumi_user;

--
-- Name: fiscal_periods_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.fiscal_periods_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.fiscal_periods_id_seq OWNER TO sakumi_user;

--
-- Name: fiscal_periods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.fiscal_periods_id_seq OWNED BY public.fiscal_periods.id;


--
-- Name: invoice_items; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.invoice_items (
    id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    student_obligation_id bigint NOT NULL,
    fee_type_id bigint NOT NULL,
    description character varying(255),
    amount numeric(15,2) NOT NULL,
    month smallint,
    year smallint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.invoice_items OWNER TO sakumi_user;

--
-- Name: invoice_items_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.invoice_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.invoice_items_id_seq OWNER TO sakumi_user;

--
-- Name: invoice_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.invoice_items_id_seq OWNED BY public.invoice_items.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    invoice_number character varying(255) NOT NULL,
    student_id bigint NOT NULL,
    period_type character varying(20) DEFAULT 'monthly'::character varying NOT NULL,
    period_identifier character varying(30) NOT NULL,
    invoice_date date NOT NULL,
    due_date date NOT NULL,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    paid_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'unpaid'::character varying NOT NULL,
    notes text,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    academic_year_id bigint,
    student_enrollment_id bigint,
    CONSTRAINT invoices_period_type_check CHECK (((period_type)::text = ANY ((ARRAY['monthly'::character varying, 'annual'::character varying, 'registration'::character varying])::text[]))),
    CONSTRAINT invoices_status_check CHECK (((status)::text = ANY ((ARRAY['unpaid'::character varying, 'partially_paid'::character varying, 'paid'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.invoices OWNER TO sakumi_user;

--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.invoices_id_seq OWNER TO sakumi_user;

--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO sakumi_user;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO sakumi_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobs_id_seq OWNER TO sakumi_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: journal_entries_v2; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.journal_entries_v2 (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    accounting_event_id bigint NOT NULL,
    line_no integer NOT NULL,
    entry_date date NOT NULL,
    account_id bigint NOT NULL,
    account_code character varying(30) NOT NULL,
    description character varying(255),
    debit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(3) DEFAULT 'IDR'::character varying NOT NULL,
    meta json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.journal_entries_v2 OWNER TO sakumi_user;

--
-- Name: journal_entries_v2_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.journal_entries_v2_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.journal_entries_v2_id_seq OWNER TO sakumi_user;

--
-- Name: journal_entries_v2_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.journal_entries_v2_id_seq OWNED BY public.journal_entries_v2.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO sakumi_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO sakumi_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO sakumi_user;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO sakumi_user;

--
-- Name: settlement_allocations; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.settlement_allocations (
    id bigint NOT NULL,
    settlement_id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    amount numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.settlement_allocations OWNER TO sakumi_user;

--
-- Name: settlements; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.settlements (
    id bigint NOT NULL,
    settlement_number character varying(255) NOT NULL,
    student_id bigint NOT NULL,
    payment_date date NOT NULL,
    payment_method character varying(20) DEFAULT 'cash'::character varying NOT NULL,
    total_amount numeric(15,2) NOT NULL,
    allocated_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reference_number character varying(255),
    notes text,
    status character varying(20) DEFAULT 'completed'::character varying NOT NULL,
    created_by bigint NOT NULL,
    cancelled_at timestamp(0) without time zone,
    cancelled_by bigint,
    cancellation_reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    voided_at timestamp(0) without time zone,
    voided_by bigint,
    void_reason text,
    CONSTRAINT settlements_payment_method_check CHECK (((payment_method)::text = ANY ((ARRAY['cash'::character varying, 'transfer'::character varying, 'qris'::character varying])::text[]))),
    CONSTRAINT settlements_status_check CHECK (((status)::text = ANY ((ARRAY['completed'::character varying, 'cancelled'::character varying, 'void'::character varying])::text[])))
);


ALTER TABLE public.settlements OWNER TO sakumi_user;

--
-- Name: mv_ar_outstanding; Type: MATERIALIZED VIEW; Schema: public; Owner: sakumi_user
--

CREATE MATERIALIZED VIEW public.mv_ar_outstanding AS
 SELECT i.id AS invoice_id,
    i.unit_id,
    i.student_id,
    i.invoice_number,
    i.period_type,
    i.period_identifier,
    i.invoice_date,
    i.due_date,
    i.total_amount,
    i.status,
    COALESCE(sa_sum.settled_amount, (0)::numeric) AS settled_amount,
    (i.total_amount - COALESCE(sa_sum.settled_amount, (0)::numeric)) AS outstanding_amount
   FROM (public.invoices i
     LEFT JOIN ( SELECT sa.invoice_id,
            sum(sa.amount) AS settled_amount
           FROM (public.settlement_allocations sa
             JOIN public.settlements s ON (((s.id = sa.settlement_id) AND ((s.status)::text = 'completed'::text))))
          GROUP BY sa.invoice_id) sa_sum ON ((sa_sum.invoice_id = i.id)))
  WHERE (((i.status)::text <> 'cancelled'::text) AND (i.total_amount > COALESCE(sa_sum.settled_amount, (0)::numeric)))
  WITH NO DATA;


ALTER TABLE public.mv_ar_outstanding OWNER TO sakumi_user;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    transaction_number character varying(50) NOT NULL,
    transaction_date date NOT NULL,
    type character varying(20) NOT NULL,
    student_id bigint,
    payment_method character varying(20),
    total_amount numeric(15,2) NOT NULL,
    description text,
    receipt_path character varying(255),
    proof_path character varying(255),
    status character varying(20) DEFAULT 'completed'::character varying NOT NULL,
    cancelled_at timestamp(0) without time zone,
    cancelled_by bigint,
    cancellation_reason text,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    account_id bigint,
    category_id bigint,
    unit_id bigint NOT NULL,
    CONSTRAINT chk_payment_method CHECK (((payment_method)::text = ANY ((ARRAY['cash'::character varying, 'transfer'::character varying, 'qris'::character varying])::text[]))),
    CONSTRAINT chk_transaction_status CHECK (((status)::text = ANY ((ARRAY['completed'::character varying, 'cancelled'::character varying])::text[]))),
    CONSTRAINT chk_transaction_type CHECK (((type)::text = ANY ((ARRAY['income'::character varying, 'expense'::character varying])::text[])))
);


ALTER TABLE public.transactions OWNER TO sakumi_user;

--
-- Name: mv_daily_cash_summary; Type: MATERIALIZED VIEW; Schema: public; Owner: sakumi_user
--

CREATE MATERIALIZED VIEW public.mv_daily_cash_summary AS
 SELECT daily.entry_date,
    daily.unit_id,
    sum(daily.debit) AS total_debit,
    sum(daily.credit) AS total_credit,
    (sum(daily.debit) - sum(daily.credit)) AS net
   FROM ( SELECT s.payment_date AS entry_date,
            s.unit_id,
            s.allocated_amount AS debit,
            0 AS credit
           FROM public.settlements s
          WHERE (((s.status)::text = 'completed'::text) AND ((s.payment_method)::text = 'cash'::text))
        UNION ALL
         SELECT t.transaction_date AS entry_date,
            t.unit_id,
                CASE
                    WHEN ((t.type)::text = 'income'::text) THEN t.total_amount
                    ELSE (0)::numeric
                END AS debit,
                CASE
                    WHEN ((t.type)::text = 'expense'::text) THEN t.total_amount
                    ELSE (0)::numeric
                END AS credit
           FROM public.transactions t
          WHERE (((t.status)::text = 'completed'::text) AND ((t.payment_method)::text = 'cash'::text) AND (((t.type)::text = 'expense'::text) OR (((t.type)::text = 'income'::text) AND (t.student_id IS NULL))))) daily
  GROUP BY daily.entry_date, daily.unit_id
  WITH NO DATA;


ALTER TABLE public.mv_daily_cash_summary OWNER TO sakumi_user;

--
-- Name: notifications; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    student_id bigint NOT NULL,
    type character varying(50) NOT NULL,
    message text NOT NULL,
    recipient_phone character varying(20),
    whatsapp_status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    whatsapp_sent_at timestamp(0) without time zone,
    whatsapp_response text,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    CONSTRAINT chk_whatsapp_status CHECK (((whatsapp_status)::text = ANY ((ARRAY['pending'::character varying, 'sent'::character varying, 'failed'::character varying])::text[])))
);


ALTER TABLE public.notifications OWNER TO sakumi_user;

--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.notifications_id_seq OWNER TO sakumi_user;

--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO sakumi_user;

--
-- Name: payment_allocations_v2; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.payment_allocations_v2 (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    accounting_event_id bigint NOT NULL,
    payment_source_type character varying(100) NOT NULL,
    payment_source_id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    allocated_amount numeric(15,2) NOT NULL,
    meta json,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.payment_allocations_v2 OWNER TO sakumi_user;

--
-- Name: payment_allocations_v2_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.payment_allocations_v2_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payment_allocations_v2_id_seq OWNER TO sakumi_user;

--
-- Name: payment_allocations_v2_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.payment_allocations_v2_id_seq OWNED BY public.payment_allocations_v2.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO sakumi_user;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.permissions_id_seq OWNER TO sakumi_user;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: promotion_batch_students; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.promotion_batch_students (
    id bigint NOT NULL,
    promotion_batch_id bigint NOT NULL,
    student_id bigint NOT NULL,
    from_enrollment_id bigint NOT NULL,
    action character varying(20) NOT NULL,
    to_class_id bigint,
    reason character varying(255),
    is_applied boolean DEFAULT false NOT NULL,
    applied_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT promotion_batch_students_action_check CHECK (((action)::text = ANY ((ARRAY['promote'::character varying, 'retain'::character varying, 'graduate'::character varying])::text[])))
);


ALTER TABLE public.promotion_batch_students OWNER TO sakumi_user;

--
-- Name: promotion_batch_students_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.promotion_batch_students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.promotion_batch_students_id_seq OWNER TO sakumi_user;

--
-- Name: promotion_batch_students_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.promotion_batch_students_id_seq OWNED BY public.promotion_batch_students.id;


--
-- Name: promotion_batches; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.promotion_batches (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    from_academic_year_id bigint NOT NULL,
    to_academic_year_id bigint NOT NULL,
    effective_date date NOT NULL,
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    created_by bigint NOT NULL,
    approved_by bigint,
    applied_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT promotion_batches_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'approved'::character varying, 'applied'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.promotion_batches OWNER TO sakumi_user;

--
-- Name: promotion_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.promotion_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.promotion_batches_id_seq OWNER TO sakumi_user;

--
-- Name: promotion_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.promotion_batches_id_seq OWNED BY public.promotion_batches.id;


--
-- Name: receipt_print_logs; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.receipt_print_logs (
    id bigint NOT NULL,
    receipt_id bigint NOT NULL,
    user_id bigint,
    printed_at timestamp(0) without time zone NOT NULL,
    ip_address character varying(45),
    device text,
    reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.receipt_print_logs OWNER TO sakumi_user;

--
-- Name: receipt_print_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.receipt_print_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.receipt_print_logs_id_seq OWNER TO sakumi_user;

--
-- Name: receipt_print_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.receipt_print_logs_id_seq OWNED BY public.receipt_print_logs.id;


--
-- Name: receipts; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.receipts (
    id bigint NOT NULL,
    transaction_id bigint,
    invoice_id bigint,
    issued_at timestamp(0) without time zone NOT NULL,
    printed_at timestamp(0) without time zone,
    verification_code character varying(32) NOT NULL,
    print_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    settlement_id bigint
);


ALTER TABLE public.receipts OWNER TO sakumi_user;

--
-- Name: receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.receipts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.receipts_id_seq OWNER TO sakumi_user;

--
-- Name: receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.receipts_id_seq OWNED BY public.receipts.id;


--
-- Name: reversals; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.reversals (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    original_accounting_event_id bigint NOT NULL,
    reversal_accounting_event_id bigint NOT NULL,
    reason text,
    reversed_by bigint,
    reversed_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.reversals OWNER TO sakumi_user;

--
-- Name: reversals_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.reversals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.reversals_id_seq OWNER TO sakumi_user;

--
-- Name: reversals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.reversals_id_seq OWNED BY public.reversals.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO sakumi_user;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO sakumi_user;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.roles_id_seq OWNER TO sakumi_user;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO sakumi_user;

--
-- Name: settings; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    key character varying(100) NOT NULL,
    value text,
    type character varying(20) DEFAULT 'string'::character varying NOT NULL,
    "group" character varying(50) DEFAULT 'system'::character varying NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_setting_type CHECK (((type)::text = ANY ((ARRAY['string'::character varying, 'number'::character varying, 'boolean'::character varying, 'json'::character varying])::text[])))
);


ALTER TABLE public.settings OWNER TO sakumi_user;

--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.settings_id_seq OWNER TO sakumi_user;

--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: settlement_allocations_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.settlement_allocations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.settlement_allocations_id_seq OWNER TO sakumi_user;

--
-- Name: settlement_allocations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.settlement_allocations_id_seq OWNED BY public.settlement_allocations.id;


--
-- Name: settlements_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.settlements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.settlements_id_seq OWNER TO sakumi_user;

--
-- Name: settlements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.settlements_id_seq OWNED BY public.settlements.id;


--
-- Name: student_categories; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.student_categories (
    id bigint NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    discount_percentage numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.student_categories OWNER TO sakumi_user;

--
-- Name: student_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.student_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.student_categories_id_seq OWNER TO sakumi_user;

--
-- Name: student_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.student_categories_id_seq OWNED BY public.student_categories.id;


--
-- Name: student_enrollments; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.student_enrollments (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    student_id bigint NOT NULL,
    academic_year_id bigint NOT NULL,
    class_id bigint NOT NULL,
    start_date date NOT NULL,
    end_date date,
    is_current boolean DEFAULT true NOT NULL,
    entry_status character varying(20) DEFAULT 'new'::character varying NOT NULL,
    exit_status character varying(20),
    promotion_batch_id bigint,
    previous_enrollment_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT student_enrollments_entry_status_check CHECK (((entry_status)::text = ANY ((ARRAY['new'::character varying, 'promoted'::character varying, 'retained'::character varying, 'transferred_in'::character varying])::text[]))),
    CONSTRAINT student_enrollments_exit_status_check CHECK (((exit_status IS NULL) OR ((exit_status)::text = ANY ((ARRAY['promoted'::character varying, 'retained'::character varying, 'graduated'::character varying, 'transferred_out'::character varying, 'dropout'::character varying])::text[]))))
);


ALTER TABLE public.student_enrollments OWNER TO sakumi_user;

--
-- Name: student_enrollments_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.student_enrollments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.student_enrollments_id_seq OWNER TO sakumi_user;

--
-- Name: student_enrollments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.student_enrollments_id_seq OWNED BY public.student_enrollments.id;


--
-- Name: student_fee_mappings; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.student_fee_mappings (
    id bigint NOT NULL,
    unit_id bigint NOT NULL,
    student_id bigint NOT NULL,
    fee_matrix_id bigint NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    is_active boolean DEFAULT true NOT NULL,
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_sfm_effective_dates CHECK (((effective_to IS NULL) OR (effective_to >= effective_from)))
);


ALTER TABLE public.student_fee_mappings OWNER TO sakumi_user;

--
-- Name: student_fee_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.student_fee_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.student_fee_mappings_id_seq OWNER TO sakumi_user;

--
-- Name: student_fee_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.student_fee_mappings_id_seq OWNED BY public.student_fee_mappings.id;


--
-- Name: student_obligations; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.student_obligations (
    id bigint NOT NULL,
    student_id bigint NOT NULL,
    fee_type_id bigint NOT NULL,
    month smallint NOT NULL,
    year smallint NOT NULL,
    amount numeric(15,2) NOT NULL,
    is_paid boolean DEFAULT false NOT NULL,
    paid_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    paid_at timestamp(0) without time zone,
    transaction_item_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    academic_year_id bigint,
    student_enrollment_id bigint,
    class_id_snapshot bigint
);


ALTER TABLE public.student_obligations OWNER TO sakumi_user;

--
-- Name: student_obligations_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.student_obligations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.student_obligations_id_seq OWNER TO sakumi_user;

--
-- Name: student_obligations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.student_obligations_id_seq OWNED BY public.student_obligations.id;


--
-- Name: students; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.students (
    id bigint NOT NULL,
    nis character varying(20) NOT NULL,
    nisn character varying(20),
    name character varying(255) NOT NULL,
    class_id bigint NOT NULL,
    category_id bigint NOT NULL,
    gender character(1),
    birth_date date,
    birth_place character varying(100),
    parent_name character varying(255),
    parent_phone character varying(20),
    parent_whatsapp character varying(20),
    address text,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    enrollment_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    unit_id bigint NOT NULL,
    CONSTRAINT chk_students_gender CHECK ((gender = ANY (ARRAY['L'::bpchar, 'P'::bpchar]))),
    CONSTRAINT chk_students_status CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'graduated'::character varying, 'dropout'::character varying, 'transferred'::character varying])::text[])))
);


ALTER TABLE public.students OWNER TO sakumi_user;

--
-- Name: students_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.students_id_seq OWNER TO sakumi_user;

--
-- Name: students_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.students_id_seq OWNED BY public.students.id;


--
-- Name: transaction_items; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.transaction_items (
    id bigint NOT NULL,
    transaction_id bigint NOT NULL,
    fee_type_id bigint NOT NULL,
    description character varying(255),
    amount numeric(15,2) NOT NULL,
    month smallint,
    year smallint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.transaction_items OWNER TO sakumi_user;

--
-- Name: transaction_items_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.transaction_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transaction_items_id_seq OWNER TO sakumi_user;

--
-- Name: transaction_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.transaction_items_id_seq OWNED BY public.transaction_items.id;


--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transactions_id_seq OWNER TO sakumi_user;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: units; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.units (
    id bigint NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(120) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.units OWNER TO sakumi_user;

--
-- Name: units_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.units_id_seq OWNER TO sakumi_user;

--
-- Name: units_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.units_id_seq OWNED BY public.units.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    unit_id bigint NOT NULL
);


ALTER TABLE public.users OWNER TO sakumi_user;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: sakumi_user
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO sakumi_user;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sakumi_user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: academic_years id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.academic_years ALTER COLUMN id SET DEFAULT nextval('public.academic_years_id_seq'::regclass);


--
-- Name: account_mappings id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.account_mappings ALTER COLUMN id SET DEFAULT nextval('public.account_mappings_id_seq'::regclass);


--
-- Name: accounting_events id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events ALTER COLUMN id SET DEFAULT nextval('public.accounting_events_id_seq'::regclass);


--
-- Name: accounts id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounts ALTER COLUMN id SET DEFAULT nextval('public.accounts_id_seq'::regclass);


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: admission_period_quotas id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas ALTER COLUMN id SET DEFAULT nextval('public.admission_period_quotas_id_seq'::regclass);


--
-- Name: admission_periods id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_periods ALTER COLUMN id SET DEFAULT nextval('public.admission_periods_id_seq'::regclass);


--
-- Name: applicants id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants ALTER COLUMN id SET DEFAULT nextval('public.applicants_id_seq'::regclass);


--
-- Name: bank_reconciliation_lines id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_lines ALTER COLUMN id SET DEFAULT nextval('public.bank_reconciliation_lines_id_seq'::regclass);


--
-- Name: bank_reconciliation_logs id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_logs ALTER COLUMN id SET DEFAULT nextval('public.bank_reconciliation_logs_id_seq'::regclass);


--
-- Name: bank_reconciliation_sessions id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions ALTER COLUMN id SET DEFAULT nextval('public.bank_reconciliation_sessions_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: chart_of_accounts id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.chart_of_accounts ALTER COLUMN id SET DEFAULT nextval('public.chart_of_accounts_id_seq'::regclass);


--
-- Name: class_promotion_paths id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths ALTER COLUMN id SET DEFAULT nextval('public.class_promotion_paths_id_seq'::regclass);


--
-- Name: classes id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.classes ALTER COLUMN id SET DEFAULT nextval('public.classes_id_seq'::regclass);


--
-- Name: document_sequences id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.document_sequences ALTER COLUMN id SET DEFAULT nextval('public.document_sequences_id_seq'::regclass);


--
-- Name: expense_budgets id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets ALTER COLUMN id SET DEFAULT nextval('public.expense_budgets_id_seq'::regclass);


--
-- Name: expense_entries id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries ALTER COLUMN id SET DEFAULT nextval('public.expense_entries_id_seq'::regclass);


--
-- Name: expense_fee_categories id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_categories ALTER COLUMN id SET DEFAULT nextval('public.expense_fee_categories_id_seq'::regclass);


--
-- Name: expense_fee_subcategories id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_subcategories ALTER COLUMN id SET DEFAULT nextval('public.expense_fee_subcategories_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fee_matrix id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix ALTER COLUMN id SET DEFAULT nextval('public.fee_matrix_id_seq'::regclass);


--
-- Name: fee_types id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_types ALTER COLUMN id SET DEFAULT nextval('public.fee_types_id_seq'::regclass);


--
-- Name: fiscal_periods id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fiscal_periods ALTER COLUMN id SET DEFAULT nextval('public.fiscal_periods_id_seq'::regclass);


--
-- Name: invoice_items id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items ALTER COLUMN id SET DEFAULT nextval('public.invoice_items_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: journal_entries_v2 id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2 ALTER COLUMN id SET DEFAULT nextval('public.journal_entries_v2_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: payment_allocations_v2 id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.payment_allocations_v2 ALTER COLUMN id SET DEFAULT nextval('public.payment_allocations_v2_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: promotion_batch_students id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students ALTER COLUMN id SET DEFAULT nextval('public.promotion_batch_students_id_seq'::regclass);


--
-- Name: promotion_batches id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches ALTER COLUMN id SET DEFAULT nextval('public.promotion_batches_id_seq'::regclass);


--
-- Name: receipt_print_logs id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipt_print_logs ALTER COLUMN id SET DEFAULT nextval('public.receipt_print_logs_id_seq'::regclass);


--
-- Name: receipts id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts ALTER COLUMN id SET DEFAULT nextval('public.receipts_id_seq'::regclass);


--
-- Name: reversals id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals ALTER COLUMN id SET DEFAULT nextval('public.reversals_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: settlement_allocations id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlement_allocations ALTER COLUMN id SET DEFAULT nextval('public.settlement_allocations_id_seq'::regclass);


--
-- Name: settlements id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements ALTER COLUMN id SET DEFAULT nextval('public.settlements_id_seq'::regclass);


--
-- Name: student_categories id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_categories ALTER COLUMN id SET DEFAULT nextval('public.student_categories_id_seq'::regclass);


--
-- Name: student_enrollments id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments ALTER COLUMN id SET DEFAULT nextval('public.student_enrollments_id_seq'::regclass);


--
-- Name: student_fee_mappings id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings ALTER COLUMN id SET DEFAULT nextval('public.student_fee_mappings_id_seq'::regclass);


--
-- Name: student_obligations id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations ALTER COLUMN id SET DEFAULT nextval('public.student_obligations_id_seq'::regclass);


--
-- Name: students id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students ALTER COLUMN id SET DEFAULT nextval('public.students_id_seq'::regclass);


--
-- Name: transaction_items id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transaction_items ALTER COLUMN id SET DEFAULT nextval('public.transaction_items_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: units id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.units ALTER COLUMN id SET DEFAULT nextval('public.units_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: academic_years; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.academic_years (id, unit_id, code, start_date, end_date, status, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: account_mappings; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.account_mappings (id, unit_id, event_type, line_key, entry_side, account_code, priority, is_active, description, filters, created_at, updated_at) FROM stdin;
1	1	invoice.created	receivable	debit	110100	10	t	Pengakuan piutang dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
2	1	invoice.created	revenue	credit	410100	20	t	Pengakuan pendapatan dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
3	1	payment.posted	cash	debit	110200	10	t	Penerimaan kas pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
4	1	payment.posted	receivable	credit	110100	20	t	Pelunasan piutang pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
5	1	payment.direct.posted	cash	debit	110200	10	t	Penerimaan kas transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
6	1	payment.direct.posted	revenue	credit	410100	20	t	Pengakuan pendapatan transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
7	1	settlement.applied	cash	debit	110200	10	t	Penerimaan kas settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
8	1	settlement.applied	receivable	credit	110100	20	t	Pelunasan piutang settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
9	1	expense.posted	expense	debit	510100	10	t	Pengakuan beban transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
10	1	expense.posted	cash	credit	110200	20	t	Pengeluaran kas transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
11	2	invoice.created	receivable	debit	110100	10	t	Pengakuan piutang dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
12	2	invoice.created	revenue	credit	410100	20	t	Pengakuan pendapatan dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
13	2	payment.posted	cash	debit	110200	10	t	Penerimaan kas pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
14	2	payment.posted	receivable	credit	110100	20	t	Pelunasan piutang pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
15	2	payment.direct.posted	cash	debit	110200	10	t	Penerimaan kas transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
16	2	payment.direct.posted	revenue	credit	410100	20	t	Pengakuan pendapatan transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
17	2	settlement.applied	cash	debit	110200	10	t	Penerimaan kas settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
18	2	settlement.applied	receivable	credit	110100	20	t	Pelunasan piutang settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
19	2	expense.posted	expense	debit	510100	10	t	Pengakuan beban transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
20	2	expense.posted	cash	credit	110200	20	t	Pengeluaran kas transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
21	3	invoice.created	receivable	debit	110100	10	t	Pengakuan piutang dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
22	3	invoice.created	revenue	credit	410100	20	t	Pengakuan pendapatan dari invoice	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
23	3	payment.posted	cash	debit	110200	10	t	Penerimaan kas pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
24	3	payment.posted	receivable	credit	110100	20	t	Pelunasan piutang pembayaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
25	3	payment.direct.posted	cash	debit	110200	10	t	Penerimaan kas transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
26	3	payment.direct.posted	revenue	credit	410100	20	t	Pengakuan pendapatan transaksi langsung	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
27	3	settlement.applied	cash	debit	110200	10	t	Penerimaan kas settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
28	3	settlement.applied	receivable	credit	110100	20	t	Pelunasan piutang settlement	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
29	3	expense.posted	expense	debit	510100	10	t	Pengakuan beban transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
30	3	expense.posted	cash	credit	110200	20	t	Pengeluaran kas transaksi pengeluaran	\N	2026-03-05 10:08:52	2026-03-05 10:08:52
\.


--
-- Data for Name: accounting_events; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.accounting_events (id, unit_id, event_uuid, event_type, source_type, source_id, idempotency_key, effective_date, occurred_at, fiscal_period_id, is_reversal, reversal_of_event_id, status, created_by, payload, created_at) FROM stdin;
\.


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.accounts (id, code, name, type, opening_balance, current_balance, is_active, created_at, updated_at, unit_id) FROM stdin;
\.


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid) FROM stdin;
1	default	created	App\\Models\\FeeType	1	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
2	default	created	App\\Models\\FeeType	2	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
3	default	created	App\\Models\\FeeType	3	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
4	default	created	App\\Models\\FeeType	4	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
5	default	created	App\\Models\\FeeType	5	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
6	default	created	App\\Models\\FeeType	6	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
7	default	created	App\\Models\\FeeType	7	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
8	default	created	App\\Models\\FeeType	8	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
9	default	created	App\\Models\\FeeType	9	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
10	default	created	App\\Models\\FeeType	10	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
11	default	created	App\\Models\\FeeType	11	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
12	default	created	App\\Models\\FeeType	12	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
13	default	created	App\\Models\\FeeType	13	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
14	default	created	App\\Models\\FeeType	14	\N	\N	[]	2026-03-05 10:08:53	2026-03-05 10:08:53	created	\N
15	default	created	App\\Models\\FeeType	15	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
16	default	created	App\\Models\\FeeType	16	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
17	default	created	App\\Models\\FeeType	17	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
18	default	created	App\\Models\\FeeType	18	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
19	default	created	App\\Models\\FeeType	19	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
20	default	created	App\\Models\\FeeType	20	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
21	default	created	App\\Models\\FeeType	21	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
22	default	created	App\\Models\\FeeType	22	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
23	default	created	App\\Models\\FeeType	23	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
24	default	created	App\\Models\\FeeType	24	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
25	default	created	App\\Models\\FeeType	25	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
26	default	created	App\\Models\\FeeType	26	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
27	default	created	App\\Models\\FeeType	27	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
28	default	created	App\\Models\\FeeType	28	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
29	default	created	App\\Models\\FeeType	29	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
30	default	created	App\\Models\\FeeType	30	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
31	default	created	App\\Models\\FeeType	31	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
32	default	created	App\\Models\\FeeType	32	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
33	default	created	App\\Models\\FeeType	33	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
34	default	created	App\\Models\\FeeType	34	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
35	default	created	App\\Models\\FeeType	35	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
36	default	created	App\\Models\\FeeType	36	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
37	default	created	App\\Models\\FeeType	37	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
38	default	created	App\\Models\\FeeType	38	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
39	default	created	App\\Models\\FeeType	39	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
40	default	created	App\\Models\\FeeType	40	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
41	default	created	App\\Models\\FeeType	41	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
42	default	created	App\\Models\\FeeType	42	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
43	default	created	App\\Models\\FeeType	43	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
44	default	created	App\\Models\\FeeType	44	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
45	default	created	App\\Models\\FeeType	45	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
46	default	created	App\\Models\\FeeType	46	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
47	default	created	App\\Models\\FeeType	47	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
48	default	created	App\\Models\\FeeType	48	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
49	default	created	App\\Models\\FeeType	49	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
50	default	created	App\\Models\\FeeType	50	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
51	default	created	App\\Models\\FeeType	51	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
52	default	created	App\\Models\\FeeType	52	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
53	default	created	App\\Models\\FeeType	53	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
54	default	created	App\\Models\\FeeType	54	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
55	default	created	App\\Models\\FeeType	55	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
56	default	created	App\\Models\\FeeType	56	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
57	default	created	App\\Models\\FeeType	57	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
58	default	created	App\\Models\\FeeType	58	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
59	default	created	App\\Models\\FeeType	59	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
60	default	created	App\\Models\\FeeType	60	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
61	default	created	App\\Models\\FeeType	61	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
62	default	created	App\\Models\\FeeType	62	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
63	default	created	App\\Models\\FeeType	63	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
64	default	created	App\\Models\\FeeType	64	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
65	default	created	App\\Models\\FeeType	65	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
66	default	created	App\\Models\\FeeType	66	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
67	default	created	App\\Models\\FeeType	67	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
68	default	created	App\\Models\\FeeType	68	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
69	default	created	App\\Models\\FeeType	69	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
70	default	created	App\\Models\\FeeType	70	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
71	default	created	App\\Models\\FeeType	71	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
72	default	created	App\\Models\\FeeType	72	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
73	default	created	App\\Models\\FeeType	73	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
74	default	created	App\\Models\\FeeType	74	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
75	default	created	App\\Models\\FeeType	75	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
76	default	created	App\\Models\\FeeType	76	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
77	default	created	App\\Models\\FeeType	77	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
78	default	created	App\\Models\\FeeType	78	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
79	default	created	App\\Models\\FeeType	79	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
80	default	created	App\\Models\\FeeType	80	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
81	default	created	App\\Models\\FeeType	81	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
82	default	created	App\\Models\\FeeType	82	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
83	default	created	App\\Models\\FeeType	83	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
84	default	created	App\\Models\\FeeType	84	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
85	default	created	App\\Models\\FeeType	85	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
86	default	created	App\\Models\\FeeType	86	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
87	default	created	App\\Models\\FeeType	87	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
88	default	created	App\\Models\\FeeType	88	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
89	default	created	App\\Models\\FeeType	89	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
90	default	created	App\\Models\\FeeType	90	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
91	default	created	App\\Models\\FeeType	91	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
92	default	created	App\\Models\\FeeType	92	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
93	default	created	App\\Models\\FeeType	93	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
94	default	created	App\\Models\\FeeType	94	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
95	default	created	App\\Models\\FeeType	95	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
96	default	created	App\\Models\\FeeType	96	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
97	default	created	App\\Models\\FeeType	97	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
98	default	created	App\\Models\\FeeType	98	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
99	default	created	App\\Models\\FeeType	99	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
100	default	created	App\\Models\\FeeType	100	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
101	default	created	App\\Models\\FeeType	101	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
102	default	created	App\\Models\\FeeType	102	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
103	default	created	App\\Models\\FeeType	103	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
104	default	created	App\\Models\\FeeType	104	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
105	default	created	App\\Models\\FeeType	105	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
106	default	created	App\\Models\\FeeType	106	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
107	default	created	App\\Models\\FeeType	107	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
108	default	created	App\\Models\\FeeType	108	\N	\N	[]	2026-03-05 10:08:54	2026-03-05 10:08:54	created	\N
109	default	created	App\\Models\\FeeType	109	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
110	default	created	App\\Models\\FeeType	110	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
111	default	created	App\\Models\\FeeType	111	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
112	default	created	App\\Models\\FeeType	112	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
113	default	created	App\\Models\\FeeType	113	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
114	default	created	App\\Models\\FeeType	114	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
115	default	created	App\\Models\\FeeType	115	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
116	default	created	App\\Models\\FeeType	116	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
117	default	created	App\\Models\\FeeType	117	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
118	default	created	App\\Models\\FeeType	118	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
119	default	created	App\\Models\\FeeType	119	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
120	default	created	App\\Models\\FeeType	120	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
121	default	created	App\\Models\\FeeType	121	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
122	default	created	App\\Models\\FeeType	122	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
123	default	created	App\\Models\\FeeType	123	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
124	default	created	App\\Models\\FeeType	124	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
125	default	created	App\\Models\\FeeType	125	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
126	default	created	App\\Models\\FeeType	126	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
127	default	created	App\\Models\\FeeType	127	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
128	default	created	App\\Models\\FeeType	128	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
129	default	created	App\\Models\\FeeType	129	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
130	default	created	App\\Models\\FeeType	130	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
131	default	created	App\\Models\\FeeType	131	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
132	default	created	App\\Models\\FeeType	132	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
133	default	created	App\\Models\\FeeType	133	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
134	default	created	App\\Models\\FeeType	134	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
135	default	created	App\\Models\\FeeType	135	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
136	default	created	App\\Models\\FeeType	136	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
137	default	created	App\\Models\\FeeType	137	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
138	default	created	App\\Models\\FeeType	138	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
139	default	created	App\\Models\\FeeType	139	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
140	default	created	App\\Models\\FeeType	140	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
141	default	created	App\\Models\\FeeType	141	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
142	default	created	App\\Models\\FeeType	142	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
143	default	created	App\\Models\\FeeType	143	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
144	default	created	App\\Models\\FeeType	144	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
145	default	created	App\\Models\\FeeType	145	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
146	default	created	App\\Models\\FeeType	146	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
147	default	created	App\\Models\\FeeType	147	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
148	default	created	App\\Models\\FeeType	148	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
149	default	created	App\\Models\\FeeType	149	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
150	default	created	App\\Models\\FeeType	150	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
151	default	created	App\\Models\\FeeType	151	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
152	default	created	App\\Models\\FeeType	152	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
153	default	created	App\\Models\\FeeType	153	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
154	default	created	App\\Models\\FeeType	154	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
155	default	created	App\\Models\\FeeType	155	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
156	default	created	App\\Models\\FeeType	156	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
157	default	created	App\\Models\\FeeType	157	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
158	default	created	App\\Models\\FeeType	158	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
159	default	created	App\\Models\\FeeType	159	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
160	default	created	App\\Models\\FeeType	160	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
161	default	created	App\\Models\\FeeType	161	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
162	default	created	App\\Models\\FeeType	162	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
163	default	created	App\\Models\\FeeType	163	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
164	default	created	App\\Models\\FeeType	164	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
165	default	created	App\\Models\\FeeType	165	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
166	default	created	App\\Models\\FeeType	166	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
167	default	created	App\\Models\\FeeType	167	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
168	default	created	App\\Models\\FeeType	168	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
169	default	created	App\\Models\\FeeType	169	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
170	default	created	App\\Models\\FeeType	170	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
171	default	created	App\\Models\\FeeType	171	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
172	default	created	App\\Models\\FeeType	172	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
173	default	created	App\\Models\\FeeType	173	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
174	default	created	App\\Models\\FeeType	174	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
175	default	created	App\\Models\\FeeType	175	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
176	default	created	App\\Models\\FeeType	176	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
177	default	created	App\\Models\\FeeType	177	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
178	default	created	App\\Models\\FeeType	178	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
179	default	created	App\\Models\\FeeType	179	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
180	default	created	App\\Models\\FeeType	180	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
181	default	created	App\\Models\\FeeType	181	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
182	default	created	App\\Models\\FeeType	182	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
183	default	created	App\\Models\\FeeType	183	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
184	default	created	App\\Models\\FeeType	184	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
185	default	created	App\\Models\\FeeType	185	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
186	default	created	App\\Models\\FeeType	186	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
187	default	created	App\\Models\\FeeType	187	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
188	default	created	App\\Models\\FeeType	188	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
189	default	created	App\\Models\\FeeType	189	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
190	default	created	App\\Models\\FeeType	190	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
191	default	created	App\\Models\\FeeType	191	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
192	default	created	App\\Models\\FeeType	192	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
193	default	created	App\\Models\\FeeType	193	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
194	default	created	App\\Models\\FeeType	194	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
195	default	created	App\\Models\\FeeType	195	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
196	default	created	App\\Models\\FeeType	196	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
197	default	created	App\\Models\\FeeType	197	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
198	default	created	App\\Models\\FeeType	198	\N	\N	[]	2026-03-05 10:08:55	2026-03-05 10:08:55	created	\N
199	default	created	App\\Models\\User	1	\N	\N	{"attributes":{"name":"Admin TU MI","email":"admin.tu.mi@sakumi.local","is_active":true}}	2026-03-05 10:08:56	2026-03-05 10:08:56	created	\N
200	default	created	App\\Models\\User	2	\N	\N	{"attributes":{"name":"Admin TU RA","email":"admin.tu.ra@sakumi.local","is_active":true}}	2026-03-05 10:08:57	2026-03-05 10:08:57	created	\N
201	default	created	App\\Models\\User	3	\N	\N	{"attributes":{"name":"Admin TU DTA","email":"admin.tu.dta@sakumi.local","is_active":true}}	2026-03-05 10:08:57	2026-03-05 10:08:57	created	\N
202	default	created	App\\Models\\User	4	\N	\N	{"attributes":{"name":"Staff","email":"staff@sakumi.local","is_active":true}}	2026-03-05 10:08:58	2026-03-05 10:08:58	created	\N
203	default	created	App\\Models\\User	5	\N	\N	{"attributes":{"name":"Bendahara","email":"bendahara@sakumi.local","is_active":true}}	2026-03-05 10:08:59	2026-03-05 10:08:59	created	\N
204	default	created	App\\Models\\User	6	\N	\N	{"attributes":{"name":"Kepala Sekolah","email":"kepala.sekolah@sakumi.local","is_active":true}}	2026-03-05 10:08:59	2026-03-05 10:08:59	created	\N
205	default	updated	App\\Models\\User	1	App\\Models\\User	1	{"attributes":[],"old":[]}	2026-03-05 11:29:03	2026-03-05 11:29:03	updated	\N
\.


--
-- Data for Name: admission_period_quotas; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.admission_period_quotas (id, unit_id, admission_period_id, class_id, quota, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: admission_periods; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.admission_periods (id, unit_id, name, academic_year, registration_open, registration_close, status, notes, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: applicants; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.applicants (id, unit_id, admission_period_id, registration_number, name, target_class_id, category_id, gender, birth_date, birth_place, parent_name, parent_phone, parent_whatsapp, address, previous_school, status, rejection_reason, status_changed_at, status_changed_by, student_id, created_by, notes, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: bank_reconciliation_lines; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.bank_reconciliation_lines (id, bank_reconciliation_session_id, line_date, description, reference, amount, type, match_status, matched_transaction_id, matched_by, matched_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: bank_reconciliation_logs; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.bank_reconciliation_logs (id, bank_reconciliation_session_id, action, payload, actor_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: bank_reconciliation_sessions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.bank_reconciliation_sessions (id, unit_id, bank_account_name, period_year, period_month, opening_balance, status, notes, created_by, updated_by, closed_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.categories (id, code, name, type, description, is_active, created_at, updated_at, unit_id) FROM stdin;
\.


--
-- Data for Name: chart_of_accounts; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.chart_of_accounts (id, unit_id, code, name, type, normal_balance, is_active, parent_id, meta, created_at, updated_at) FROM stdin;
1	1	110100	Piutang Siswa	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
2	1	110200	Kas dan Bank	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
3	1	410100	Pendapatan Pendidikan	revenue	credit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
4	1	510100	Beban Operasional	expense	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
5	2	110100	Piutang Siswa	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
6	2	110200	Kas dan Bank	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
7	2	410100	Pendapatan Pendidikan	revenue	credit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
8	2	510100	Beban Operasional	expense	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
9	3	110100	Piutang Siswa	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
10	3	110200	Kas dan Bank	asset	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
11	3	410100	Pendapatan Pendidikan	revenue	credit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
12	3	510100	Beban Operasional	expense	debit	t	\N	{"seed":"accounting_engine_v2"}	2026-03-05 10:08:52	2026-03-05 10:08:52
\.


--
-- Data for Name: class_promotion_paths; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.class_promotion_paths (id, unit_id, from_class_id, to_class_id, from_academic_year_id, to_academic_year_id, priority, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: classes; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.classes (id, name, level, academic_year, is_active, created_at, updated_at, unit_id, deleted_at, academic_year_id) FROM stdin;
\.


--
-- Data for Name: document_sequences; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.document_sequences (id, prefix, last_sequence, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: expense_budgets; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.expense_budgets (id, unit_id, year, month, expense_fee_subcategory_id, budget_amount, notes, created_by, updated_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: expense_entries; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.expense_entries (id, unit_id, expense_fee_subcategory_id, fee_type_id, entry_date, payment_method, vendor_name, amount, description, status, posted_transaction_id, approved_by, approved_at, created_by, updated_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: expense_fee_categories; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.expense_fee_categories (id, unit_id, code, name, sort_order, is_active, created_at, updated_at) FROM stdin;
1	1	A	Operasional Pendidikan	1	t	2026-03-05 10:08:53	2026-03-05 10:08:53
2	1	B	Kegiatan Belajar Mengajar	2	t	2026-03-05 10:08:53	2026-03-05 10:08:53
3	1	C	Operasional Kantor & Umum	3	t	2026-03-05 10:08:53	2026-03-05 10:08:53
4	1	D	Utilitas (Rutin Bulanan)	4	t	2026-03-05 10:08:54	2026-03-05 10:08:54
5	1	E	Sarana & Prasarana	5	t	2026-03-05 10:08:54	2026-03-05 10:08:54
6	1	F	Kebersihan & Keamanan	6	t	2026-03-05 10:08:54	2026-03-05 10:08:54
7	1	G	Administrasi & Legal	7	t	2026-03-05 10:08:54	2026-03-05 10:08:54
8	1	H	Kesiswaan & Kegiatan Sekolah	8	t	2026-03-05 10:08:54	2026-03-05 10:08:54
9	1	I	Keagamaan	9	t	2026-03-05 10:08:54	2026-03-05 10:08:54
10	1	J	IT & Sistem Informasi	10	t	2026-03-05 10:08:54	2026-03-05 10:08:54
11	1	K	Sosial & Kesejahteraan	11	t	2026-03-05 10:08:54	2026-03-05 10:08:54
12	1	L	Lain-lain	12	t	2026-03-05 10:08:54	2026-03-05 10:08:54
13	2	A	Operasional Pendidikan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
14	2	B	Kegiatan Belajar Mengajar	2	t	2026-03-05 10:08:54	2026-03-05 10:08:54
15	2	C	Operasional Kantor & Umum	3	t	2026-03-05 10:08:54	2026-03-05 10:08:54
16	2	D	Utilitas (Rutin Bulanan)	4	t	2026-03-05 10:08:54	2026-03-05 10:08:54
17	2	E	Sarana & Prasarana	5	t	2026-03-05 10:08:54	2026-03-05 10:08:54
18	2	F	Kebersihan & Keamanan	6	t	2026-03-05 10:08:54	2026-03-05 10:08:54
19	2	G	Administrasi & Legal	7	t	2026-03-05 10:08:54	2026-03-05 10:08:54
20	2	H	Kesiswaan & Kegiatan Sekolah	8	t	2026-03-05 10:08:54	2026-03-05 10:08:54
21	2	I	Keagamaan	9	t	2026-03-05 10:08:55	2026-03-05 10:08:55
22	2	J	IT & Sistem Informasi	10	t	2026-03-05 10:08:55	2026-03-05 10:08:55
23	2	K	Sosial & Kesejahteraan	11	t	2026-03-05 10:08:55	2026-03-05 10:08:55
24	2	L	Lain-lain	12	t	2026-03-05 10:08:55	2026-03-05 10:08:55
25	3	A	Operasional Pendidikan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
26	3	B	Kegiatan Belajar Mengajar	2	t	2026-03-05 10:08:55	2026-03-05 10:08:55
27	3	C	Operasional Kantor & Umum	3	t	2026-03-05 10:08:55	2026-03-05 10:08:55
28	3	D	Utilitas (Rutin Bulanan)	4	t	2026-03-05 10:08:55	2026-03-05 10:08:55
29	3	E	Sarana & Prasarana	5	t	2026-03-05 10:08:55	2026-03-05 10:08:55
30	3	F	Kebersihan & Keamanan	6	t	2026-03-05 10:08:55	2026-03-05 10:08:55
31	3	G	Administrasi & Legal	7	t	2026-03-05 10:08:55	2026-03-05 10:08:55
32	3	H	Kesiswaan & Kegiatan Sekolah	8	t	2026-03-05 10:08:55	2026-03-05 10:08:55
33	3	I	Keagamaan	9	t	2026-03-05 10:08:55	2026-03-05 10:08:55
34	3	J	IT & Sistem Informasi	10	t	2026-03-05 10:08:55	2026-03-05 10:08:55
35	3	K	Sosial & Kesejahteraan	11	t	2026-03-05 10:08:55	2026-03-05 10:08:55
36	3	L	Lain-lain	12	t	2026-03-05 10:08:55	2026-03-05 10:08:55
\.


--
-- Data for Name: expense_fee_subcategories; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.expense_fee_subcategories (id, unit_id, expense_fee_category_id, code, name, sort_order, is_active, created_at, updated_at) FROM stdin;
1	1	1	A1	SDM Pendidikan	1	t	2026-03-05 10:08:53	2026-03-05 10:08:53
2	1	2	B1	Operasional KBM	1	t	2026-03-05 10:08:53	2026-03-05 10:08:53
3	1	3	C1	Kantor Harian	1	t	2026-03-05 10:08:53	2026-03-05 10:08:53
4	1	4	D1	Tagihan Utilitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
5	1	5	E1	Pemeliharaan Fasilitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
6	1	6	F1	Operasional Kebersihan/Keamanan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
7	1	7	G1	Dokumen & Legalitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
8	1	8	H1	Event Kesiswaan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
9	1	9	I1	Kegiatan Keagamaan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
10	1	10	J1	Infrastruktur & Aplikasi	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
11	1	11	K1	Bantuan Sosial	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
12	1	12	L1	Biaya Umum Lainnya	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
13	2	13	A1	SDM Pendidikan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
14	2	14	B1	Operasional KBM	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
15	2	15	C1	Kantor Harian	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
16	2	16	D1	Tagihan Utilitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
17	2	17	E1	Pemeliharaan Fasilitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
18	2	18	F1	Operasional Kebersihan/Keamanan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
19	2	19	G1	Dokumen & Legalitas	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
20	2	20	H1	Event Kesiswaan	1	t	2026-03-05 10:08:54	2026-03-05 10:08:54
21	2	21	I1	Kegiatan Keagamaan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
22	2	22	J1	Infrastruktur & Aplikasi	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
23	2	23	K1	Bantuan Sosial	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
24	2	24	L1	Biaya Umum Lainnya	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
25	3	25	A1	SDM Pendidikan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
26	3	26	B1	Operasional KBM	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
27	3	27	C1	Kantor Harian	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
28	3	28	D1	Tagihan Utilitas	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
29	3	29	E1	Pemeliharaan Fasilitas	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
30	3	30	F1	Operasional Kebersihan/Keamanan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
31	3	31	G1	Dokumen & Legalitas	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
32	3	32	H1	Event Kesiswaan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
33	3	33	I1	Kegiatan Keagamaan	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
34	3	34	J1	Infrastruktur & Aplikasi	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
35	3	35	K1	Bantuan Sosial	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
36	3	36	L1	Biaya Umum Lainnya	1	t	2026-03-05 10:08:55	2026-03-05 10:08:55
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: fee_matrix; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.fee_matrix (id, fee_type_id, class_id, category_id, amount, effective_from, effective_to, is_active, notes, created_at, updated_at, unit_id, deleted_at) FROM stdin;
\.


--
-- Data for Name: fee_types; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.fee_types (id, code, name, description, is_monthly, is_active, created_at, updated_at, unit_id, expense_fee_subcategory_id, deleted_at) FROM stdin;
1	EXP-A-001-MI	Honor Guru	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
2	EXP-A-002-MI	Honor Guru Tidak Tetap / Eksternal	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
3	EXP-A-003-MI	Honor Tenaga Kependidikan (TU, Admin)	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
4	EXP-A-004-MI	Insentif Wali Kelas	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
5	EXP-A-005-MI	Insentif Pembina Ekstrakurikuler	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
6	EXP-A-006-MI	Lembur Pegawai	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
7	EXP-A-007-MI	Tunjangan Transport	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
8	EXP-A-008-MI	Tunjangan Makan	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	1	\N
9	EXP-B-001-MI	Pengadaan Buku Pelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
10	EXP-B-002-MI	Pengadaan Modul / LKS	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
11	EXP-B-003-MI	ATK Kegiatan KBM	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
12	EXP-B-004-MI	Pengadaan Media Pembelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
13	EXP-B-005-MI	Fotokopi & Percetakan Materi	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
14	EXP-B-006-MI	Biaya Praktikum	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	2	\N
15	EXP-C-001-MI	ATK Kantor	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:53	2026-03-05 10:08:53	1	3	\N
16	EXP-C-002-MI	Kertas & Tinta Printer	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
17	EXP-C-003-MI	Biaya Fotokopi	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
18	EXP-C-004-MI	Biaya Internet	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
19	EXP-C-005-MI	Biaya Telepon	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
20	EXP-C-006-MI	Biaya Konsumsi Rapat	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
21	EXP-C-007-MI	Biaya Kebersihan	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	3	\N
22	EXP-D-001-MI	Listrik	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	4	\N
23	EXP-D-002-MI	Air	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	4	\N
24	EXP-D-003-MI	Internet	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	4	\N
25	EXP-D-004-MI	Telepon	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	4	\N
26	EXP-D-005-MI	Gas	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	4	\N
27	EXP-E-001-MI	Pemeliharaan Gedung	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
28	EXP-E-002-MI	Perbaikan Ruang Kelas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
29	EXP-E-003-MI	Perbaikan Atap / Plafon	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
30	EXP-E-004-MI	Perbaikan Listrik	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
31	EXP-E-005-MI	Perbaikan AC / Kipas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
32	EXP-E-006-MI	Pembelian Peralatan Sekolah	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
33	EXP-E-007-MI	Pembelian Meja & Kursi	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	5	\N
34	EXP-F-001-MI	Honor Petugas Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	6	\N
35	EXP-F-002-MI	Honor Satpam	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	6	\N
36	EXP-F-003-MI	Alat Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	6	\N
37	EXP-F-004-MI	Bahan Pembersih	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	6	\N
38	EXP-G-001-MI	Materai	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	7	\N
39	EXP-G-002-MI	Biaya Notaris	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	7	\N
40	EXP-G-003-MI	Biaya Legalisir	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	7	\N
41	EXP-G-004-MI	Biaya Perizinan	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	7	\N
42	EXP-G-005-MI	Biaya Pengurusan Dokumen	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	7	\N
43	EXP-H-001-MI	Kegiatan Class Meeting	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
44	EXP-H-002-MI	Kegiatan PHBI / PHBN	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
45	EXP-H-003-MI	Study Tour	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
46	EXP-H-004-MI	Lomba / Kompetisi	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
47	EXP-H-005-MI	Konsumsi Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
48	EXP-H-006-MI	Transport Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	8	\N
49	EXP-I-001-MI	Honor Imam / Ustadz	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	9	\N
50	EXP-I-002-MI	Pengajian / Kajian	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	9	\N
51	EXP-I-003-MI	Kegiatan Ramadhan	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	9	\N
52	EXP-I-004-MI	Zakat / Infak / Sedekah Operasional	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	9	\N
53	EXP-I-005-MI	Pengadaan Al-Qur’an	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	9	\N
54	EXP-J-001-MI	Hosting / VPS	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
55	EXP-J-002-MI	Domain	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
56	EXP-J-003-MI	Perawatan Sistem	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
57	EXP-J-004-MI	Pengembangan Aplikasi	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
58	EXP-J-005-MI	Pembelian Komputer / Laptop	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
59	EXP-J-006-MI	Perangkat Jaringan	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	10	\N
60	EXP-K-001-MI	Santunan Siswa	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	11	\N
61	EXP-K-002-MI	Bantuan Pegawai	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	11	\N
62	EXP-K-003-MI	Dana Sosial	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	11	\N
63	EXP-K-004-MI	Bantuan Kesehatan	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	11	\N
64	EXP-L-001-MI	Biaya Tak Terduga	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	12	\N
65	EXP-L-002-MI	Biaya Administrasi Bank	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	12	\N
66	EXP-L-003-MI	Biaya Transfer	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	1	12	\N
67	EXP-A-001-RA	Honor Guru	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
68	EXP-A-002-RA	Honor Guru Tidak Tetap / Eksternal	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
69	EXP-A-003-RA	Honor Tenaga Kependidikan (TU, Admin)	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
70	EXP-A-004-RA	Insentif Wali Kelas	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
71	EXP-A-005-RA	Insentif Pembina Ekstrakurikuler	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
72	EXP-A-006-RA	Lembur Pegawai	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
73	EXP-A-007-RA	Tunjangan Transport	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
74	EXP-A-008-RA	Tunjangan Makan	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	13	\N
75	EXP-B-001-RA	Pengadaan Buku Pelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
76	EXP-B-002-RA	Pengadaan Modul / LKS	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
77	EXP-B-003-RA	ATK Kegiatan KBM	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
78	EXP-B-004-RA	Pengadaan Media Pembelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
79	EXP-B-005-RA	Fotokopi & Percetakan Materi	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
80	EXP-B-006-RA	Biaya Praktikum	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	14	\N
81	EXP-C-001-RA	ATK Kantor	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
82	EXP-C-002-RA	Kertas & Tinta Printer	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
83	EXP-C-003-RA	Biaya Fotokopi	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
84	EXP-C-004-RA	Biaya Internet	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
85	EXP-C-005-RA	Biaya Telepon	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
86	EXP-C-006-RA	Biaya Konsumsi Rapat	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
87	EXP-C-007-RA	Biaya Kebersihan	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	15	\N
88	EXP-D-001-RA	Listrik	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	16	\N
89	EXP-D-002-RA	Air	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	16	\N
90	EXP-D-003-RA	Internet	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	16	\N
91	EXP-D-004-RA	Telepon	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	16	\N
92	EXP-D-005-RA	Gas	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	16	\N
93	EXP-E-001-RA	Pemeliharaan Gedung	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
94	EXP-E-002-RA	Perbaikan Ruang Kelas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
95	EXP-E-003-RA	Perbaikan Atap / Plafon	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
96	EXP-E-004-RA	Perbaikan Listrik	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
97	EXP-E-005-RA	Perbaikan AC / Kipas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
98	EXP-E-006-RA	Pembelian Peralatan Sekolah	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
99	EXP-E-007-RA	Pembelian Meja & Kursi	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	17	\N
100	EXP-F-001-RA	Honor Petugas Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	18	\N
101	EXP-F-002-RA	Honor Satpam	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	18	\N
102	EXP-F-003-RA	Alat Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	18	\N
103	EXP-F-004-RA	Bahan Pembersih	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	18	\N
104	EXP-G-001-RA	Materai	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	19	\N
105	EXP-G-002-RA	Biaya Notaris	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	19	\N
106	EXP-G-003-RA	Biaya Legalisir	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	19	\N
107	EXP-G-004-RA	Biaya Perizinan	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	19	\N
108	EXP-G-005-RA	Biaya Pengurusan Dokumen	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	19	\N
109	EXP-H-001-RA	Kegiatan Class Meeting	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:54	2026-03-05 10:08:54	2	20	\N
110	EXP-H-002-RA	Kegiatan PHBI / PHBN	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	20	\N
111	EXP-H-003-RA	Study Tour	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	20	\N
112	EXP-H-004-RA	Lomba / Kompetisi	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	20	\N
113	EXP-H-005-RA	Konsumsi Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	20	\N
114	EXP-H-006-RA	Transport Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	20	\N
115	EXP-I-001-RA	Honor Imam / Ustadz	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	21	\N
116	EXP-I-002-RA	Pengajian / Kajian	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	21	\N
117	EXP-I-003-RA	Kegiatan Ramadhan	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	21	\N
118	EXP-I-004-RA	Zakat / Infak / Sedekah Operasional	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	21	\N
119	EXP-I-005-RA	Pengadaan Al-Qur’an	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	21	\N
120	EXP-J-001-RA	Hosting / VPS	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
121	EXP-J-002-RA	Domain	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
122	EXP-J-003-RA	Perawatan Sistem	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
123	EXP-J-004-RA	Pengembangan Aplikasi	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
124	EXP-J-005-RA	Pembelian Komputer / Laptop	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
125	EXP-J-006-RA	Perangkat Jaringan	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	22	\N
126	EXP-K-001-RA	Santunan Siswa	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	23	\N
127	EXP-K-002-RA	Bantuan Pegawai	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	23	\N
128	EXP-K-003-RA	Dana Sosial	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	23	\N
129	EXP-K-004-RA	Bantuan Kesehatan	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	23	\N
130	EXP-L-001-RA	Biaya Tak Terduga	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	24	\N
131	EXP-L-002-RA	Biaya Administrasi Bank	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	24	\N
132	EXP-L-003-RA	Biaya Transfer	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	2	24	\N
133	EXP-A-001-DTA	Honor Guru	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
134	EXP-A-002-DTA	Honor Guru Tidak Tetap / Eksternal	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
135	EXP-A-003-DTA	Honor Tenaga Kependidikan (TU, Admin)	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
136	EXP-A-004-DTA	Insentif Wali Kelas	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
137	EXP-A-005-DTA	Insentif Pembina Ekstrakurikuler	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
138	EXP-A-006-DTA	Lembur Pegawai	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
139	EXP-A-007-DTA	Tunjangan Transport	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
140	EXP-A-008-DTA	Tunjangan Makan	Operasional Pendidikan / SDM Pendidikan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	25	\N
141	EXP-B-001-DTA	Pengadaan Buku Pelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
142	EXP-B-002-DTA	Pengadaan Modul / LKS	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
143	EXP-B-003-DTA	ATK Kegiatan KBM	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
144	EXP-B-004-DTA	Pengadaan Media Pembelajaran	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
145	EXP-B-005-DTA	Fotokopi & Percetakan Materi	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
146	EXP-B-006-DTA	Biaya Praktikum	Kegiatan Belajar Mengajar / Operasional KBM	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	26	\N
147	EXP-C-001-DTA	ATK Kantor	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
148	EXP-C-002-DTA	Kertas & Tinta Printer	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
149	EXP-C-003-DTA	Biaya Fotokopi	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
150	EXP-C-004-DTA	Biaya Internet	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
151	EXP-C-005-DTA	Biaya Telepon	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
152	EXP-C-006-DTA	Biaya Konsumsi Rapat	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
153	EXP-C-007-DTA	Biaya Kebersihan	Operasional Kantor & Umum / Kantor Harian	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	27	\N
154	EXP-D-001-DTA	Listrik	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	28	\N
155	EXP-D-002-DTA	Air	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	28	\N
156	EXP-D-003-DTA	Internet	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	28	\N
157	EXP-D-004-DTA	Telepon	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	28	\N
158	EXP-D-005-DTA	Gas	Utilitas (Rutin Bulanan) / Tagihan Utilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	28	\N
159	EXP-E-001-DTA	Pemeliharaan Gedung	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
160	EXP-E-002-DTA	Perbaikan Ruang Kelas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
161	EXP-E-003-DTA	Perbaikan Atap / Plafon	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
162	EXP-E-004-DTA	Perbaikan Listrik	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
163	EXP-E-005-DTA	Perbaikan AC / Kipas	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
164	EXP-E-006-DTA	Pembelian Peralatan Sekolah	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
165	EXP-E-007-DTA	Pembelian Meja & Kursi	Sarana & Prasarana / Pemeliharaan Fasilitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	29	\N
166	EXP-F-001-DTA	Honor Petugas Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	30	\N
167	EXP-F-002-DTA	Honor Satpam	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	30	\N
168	EXP-F-003-DTA	Alat Kebersihan	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	30	\N
169	EXP-F-004-DTA	Bahan Pembersih	Kebersihan & Keamanan / Operasional Kebersihan/Keamanan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	30	\N
170	EXP-G-001-DTA	Materai	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	31	\N
171	EXP-G-002-DTA	Biaya Notaris	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	31	\N
172	EXP-G-003-DTA	Biaya Legalisir	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	31	\N
173	EXP-G-004-DTA	Biaya Perizinan	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	31	\N
174	EXP-G-005-DTA	Biaya Pengurusan Dokumen	Administrasi & Legal / Dokumen & Legalitas	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	31	\N
175	EXP-H-001-DTA	Kegiatan Class Meeting	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
176	EXP-H-002-DTA	Kegiatan PHBI / PHBN	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
177	EXP-H-003-DTA	Study Tour	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
178	EXP-H-004-DTA	Lomba / Kompetisi	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
179	EXP-H-005-DTA	Konsumsi Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
180	EXP-H-006-DTA	Transport Kegiatan	Kesiswaan & Kegiatan Sekolah / Event Kesiswaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	32	\N
181	EXP-I-001-DTA	Honor Imam / Ustadz	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	33	\N
182	EXP-I-002-DTA	Pengajian / Kajian	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	33	\N
183	EXP-I-003-DTA	Kegiatan Ramadhan	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	33	\N
184	EXP-I-004-DTA	Zakat / Infak / Sedekah Operasional	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	33	\N
185	EXP-I-005-DTA	Pengadaan Al-Qur’an	Keagamaan / Kegiatan Keagamaan	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	33	\N
186	EXP-J-001-DTA	Hosting / VPS	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
187	EXP-J-002-DTA	Domain	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
188	EXP-J-003-DTA	Perawatan Sistem	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
189	EXP-J-004-DTA	Pengembangan Aplikasi	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
190	EXP-J-005-DTA	Pembelian Komputer / Laptop	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
191	EXP-J-006-DTA	Perangkat Jaringan	IT & Sistem Informasi / Infrastruktur & Aplikasi	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	34	\N
192	EXP-K-001-DTA	Santunan Siswa	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	35	\N
193	EXP-K-002-DTA	Bantuan Pegawai	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	35	\N
194	EXP-K-003-DTA	Dana Sosial	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	35	\N
195	EXP-K-004-DTA	Bantuan Kesehatan	Sosial & Kesejahteraan / Bantuan Sosial	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	35	\N
196	EXP-L-001-DTA	Biaya Tak Terduga	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	36	\N
197	EXP-L-002-DTA	Biaya Administrasi Bank	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	36	\N
198	EXP-L-003-DTA	Biaya Transfer	Lain-lain / Biaya Umum Lainnya	f	t	2026-03-05 10:08:55	2026-03-05 10:08:55	3	36	\N
\.


--
-- Data for Name: fiscal_periods; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.fiscal_periods (id, unit_id, period_key, starts_on, ends_on, is_locked, locked_at, locked_by, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: invoice_items; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.invoice_items (id, invoice_id, student_obligation_id, fee_type_id, description, amount, month, year, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: invoices; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.invoices (id, invoice_number, student_id, period_type, period_identifier, invoice_date, due_date, total_amount, paid_amount, status, notes, created_by, created_at, updated_at, unit_id, academic_year_id, student_enrollment_id) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: journal_entries_v2; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.journal_entries_v2 (id, unit_id, accounting_event_id, line_no, entry_date, account_id, account_code, description, debit, credit, currency, meta, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_02_11_102213_create_permission_tables	1
5	2026_02_11_102215_create_activity_log_table	1
6	2026_02_11_102216_add_event_column_to_activity_log_table	1
7	2026_02_11_102217_add_batch_uuid_column_to_activity_log_table	1
8	2026_02_11_110001_create_classes_table	1
9	2026_02_11_110002_create_student_categories_table	1
10	2026_02_11_110003_create_students_table	1
11	2026_02_11_110004_create_fee_types_table	1
12	2026_02_11_110005_create_fee_matrix_table	1
13	2026_02_11_110006_create_transactions_table	1
14	2026_02_11_110007_create_transaction_items_table	1
15	2026_02_11_110008_create_student_obligations_table	1
16	2026_02_11_110009_create_notifications_table	1
17	2026_02_11_110010_create_settings_table	1
18	2026_02_11_110011_add_is_active_to_users_table	1
19	2026_02_14_100001_create_invoices_table	1
20	2026_02_14_100002_create_invoice_items_table	1
21	2026_02_14_100003_create_settlements_table	1
22	2026_02_14_100004_create_settlement_allocations_table	1
23	2026_02_15_140000_create_accounts_table	1
24	2026_02_15_140100_create_categories_table	1
25	2026_02_15_140200_add_account_and_category_to_transactions_table	1
26	2026_02_15_160000_create_units_and_add_unit_scope_to_core_tables	1
27	2026_02_16_150000_create_expense_fee_taxonomy_tables	1
28	2026_02_17_100000_add_void_columns_to_settlements_table	1
29	2026_02_18_120000_create_receipts_table	1
30	2026_02_18_120100_create_receipt_print_logs_table	1
31	2026_02_23_090000_create_chart_of_accounts_table	1
32	2026_02_23_090100_create_fiscal_periods_table	1
33	2026_02_23_090200_create_account_mappings_table	1
34	2026_02_23_090300_create_accounting_events_table	1
35	2026_02_23_090400_create_journal_entries_v2_table	1
36	2026_02_23_090500_create_payment_allocations_v2_table	1
37	2026_02_23_090600_create_reversals_table	1
38	2026_02_24_120000_add_reporting_composite_indexes	1
39	2026_02_24_130000_add_soft_deletes_to_master_tables	1
40	2026_02_24_131500_create_student_fee_mappings_table	1
41	2026_02_24_133000_create_expense_budgets_and_entries_tables	1
42	2026_02_24_133100_create_bank_reconciliation_tables	1
43	2026_02_24_150000_backfill_dangerous_permanent_delete_setting	1
44	2026_03_01_100000_create_admission_periods_table	1
45	2026_03_01_100100_create_admission_period_quotas_table	1
46	2026_03_01_100200_create_applicants_table	1
47	2026_03_01_100300_extend_invoices_period_type_for_registration	1
48	2026_03_02_100000_add_missing_unique_constraints	1
49	2026_03_02_200000_add_settlement_id_to_receipts_table	1
50	2026_03_03_090000_create_academic_years_table	1
51	2026_03_03_090100_add_academic_year_id_to_classes_table	1
52	2026_03_03_090200_create_promotion_batches_table	1
53	2026_03_03_090300_create_student_enrollments_table	1
54	2026_03_03_090400_create_class_promotion_paths_table	1
55	2026_03_03_090500_create_promotion_batch_students_table	1
56	2026_03_03_090600_add_enrollment_snapshots_to_billing_tables	1
57	2026_03_03_100000_add_financial_integrity_constraints	1
58	2026_03_03_100100_create_document_sequences_table	1
59	2026_03_03_100200_create_report_materialized_views	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
6	App\\Models\\User	1
7	App\\Models\\User	2
8	App\\Models\\User	3
11	App\\Models\\User	4
4	App\\Models\\User	4
2	App\\Models\\User	5
3	App\\Models\\User	6
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.notifications (id, student_id, type, message, recipient_phone, whatsapp_status, whatsapp_sent_at, whatsapp_response, is_read, read_at, created_at, updated_at, deleted_at, unit_id) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: payment_allocations_v2; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.payment_allocations_v2 (id, unit_id, accounting_event_id, payment_source_type, payment_source_id, invoice_id, allocated_amount, meta, created_at) FROM stdin;
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	master.classes.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
2	master.classes.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
3	master.classes.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
4	master.classes.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
5	master.categories.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
6	master.categories.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
7	master.categories.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
8	master.categories.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
9	master.fee-types.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
10	master.fee-types.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
11	master.fee-types.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
12	master.fee-types.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
13	master.fee-matrix.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
14	master.fee-matrix.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
15	master.fee-matrix.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
16	master.fee-matrix.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
17	master.student-fee-mappings.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
18	master.student-fee-mappings.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
19	master.student-fee-mappings.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
20	master.student-fee-mappings.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
21	master.students.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
22	master.students.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
23	master.students.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
24	master.students.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
25	master.students.import	web	2026-03-05 10:08:52	2026-03-05 10:08:52
26	master.students.export	web	2026-03-05 10:08:52	2026-03-05 10:08:52
27	transactions.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
28	transactions.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
29	transactions.expense.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
30	transactions.cancel	web	2026-03-05 10:08:52	2026-03-05 10:08:52
31	expenses.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
32	expenses.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
33	expenses.approve	web	2026-03-05 10:08:52	2026-03-05 10:08:52
34	expenses.budget.manage	web	2026-03-05 10:08:52	2026-03-05 10:08:52
35	expenses.report.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
36	bank-reconciliation.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
37	bank-reconciliation.manage	web	2026-03-05 10:08:52	2026-03-05 10:08:52
38	bank-reconciliation.close	web	2026-03-05 10:08:52	2026-03-05 10:08:52
39	receipts.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
40	receipts.print	web	2026-03-05 10:08:52	2026-03-05 10:08:52
41	receipts.reprint	web	2026-03-05 10:08:52	2026-03-05 10:08:52
42	invoices.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
43	invoices.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
44	invoices.generate	web	2026-03-05 10:08:52	2026-03-05 10:08:52
45	invoices.print	web	2026-03-05 10:08:52	2026-03-05 10:08:52
46	invoices.cancel	web	2026-03-05 10:08:52	2026-03-05 10:08:52
47	invoices.cancel_paid	web	2026-03-05 10:08:52	2026-03-05 10:08:52
48	settlements.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
49	settlements.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
50	settlements.cancel	web	2026-03-05 10:08:52	2026-03-05 10:08:52
51	settlements.void	web	2026-03-05 10:08:52	2026-03-05 10:08:52
52	reports.daily	web	2026-03-05 10:08:52	2026-03-05 10:08:52
53	reports.monthly	web	2026-03-05 10:08:52	2026-03-05 10:08:52
54	reports.arrears	web	2026-03-05 10:08:52	2026-03-05 10:08:52
55	reports.ar-outstanding	web	2026-03-05 10:08:52	2026-03-05 10:08:52
56	reports.collection	web	2026-03-05 10:08:52	2026-03-05 10:08:52
57	reports.student-statement	web	2026-03-05 10:08:52	2026-03-05 10:08:52
58	reports.cash-book	web	2026-03-05 10:08:52	2026-03-05 10:08:52
59	reports.export	web	2026-03-05 10:08:52	2026-03-05 10:08:52
60	admission.periods.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
61	admission.periods.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
62	admission.periods.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
63	admission.periods.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
64	admission.applicants.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
65	admission.applicants.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
66	admission.applicants.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
67	admission.applicants.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
68	admission.applicants.review	web	2026-03-05 10:08:52	2026-03-05 10:08:52
69	admission.applicants.accept	web	2026-03-05 10:08:52	2026-03-05 10:08:52
70	admission.applicants.reject	web	2026-03-05 10:08:52	2026-03-05 10:08:52
71	admission.applicants.enroll	web	2026-03-05 10:08:52	2026-03-05 10:08:52
72	dashboard.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
73	users.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
74	users.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
75	users.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
76	users.delete	web	2026-03-05 10:08:52	2026-03-05 10:08:52
77	users.manage-roles	web	2026-03-05 10:08:52	2026-03-05 10:08:52
78	settings.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
79	settings.edit	web	2026-03-05 10:08:52	2026-03-05 10:08:52
80	backup.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
81	backup.create	web	2026-03-05 10:08:52	2026-03-05 10:08:52
82	audit.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
83	notifications.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
84	notifications.retry	web	2026-03-05 10:08:52	2026-03-05 10:08:52
85	health.view	web	2026-03-05 10:08:52	2026-03-05 10:08:52
\.


--
-- Data for Name: promotion_batch_students; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.promotion_batch_students (id, promotion_batch_id, student_id, from_enrollment_id, action, to_class_id, reason, is_applied, applied_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: promotion_batches; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.promotion_batches (id, unit_id, from_academic_year_id, to_academic_year_id, effective_date, status, created_by, approved_by, applied_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: receipt_print_logs; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.receipt_print_logs (id, receipt_id, user_id, printed_at, ip_address, device, reason, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: receipts; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.receipts (id, transaction_id, invoice_id, issued_at, printed_at, verification_code, print_count, created_at, updated_at, settlement_id) FROM stdin;
\.


--
-- Data for Name: reversals; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.reversals (id, unit_id, original_accounting_event_id, reversal_accounting_event_id, reason, reversed_by, reversed_at, created_at) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
27	1
28	1
29	1
30	1
31	1
32	1
33	1
34	1
35	1
36	1
37	1
38	1
39	1
40	1
41	1
42	1
43	1
44	1
45	1
46	1
47	1
48	1
49	1
50	1
51	1
52	1
53	1
54	1
55	1
56	1
57	1
58	1
59	1
60	1
61	1
62	1
63	1
64	1
65	1
66	1
67	1
68	1
69	1
70	1
71	1
72	1
73	1
74	1
75	1
76	1
77	1
78	1
79	1
80	1
81	1
82	1
83	1
84	1
85	1
60	2
64	2
72	2
21	2
9	2
13	2
14	2
15	2
16	2
17	2
18	2
19	2
20	2
1	2
5	2
27	2
28	2
29	2
30	2
31	2
32	2
33	2
34	2
35	2
36	2
37	2
38	2
39	2
40	2
41	2
42	2
43	2
44	2
45	2
46	2
47	2
48	2
49	2
50	2
51	2
52	2
53	2
54	2
55	2
56	2
57	2
58	2
59	2
73	2
78	2
82	2
60	3
64	3
72	3
21	3
1	3
5	3
9	3
13	3
27	3
31	3
35	3
36	3
39	3
42	3
45	3
48	3
52	3
53	3
54	3
55	3
56	3
57	3
58	3
59	3
73	3
78	3
82	3
60	4
61	4
62	4
63	4
64	4
65	4
66	4
67	4
68	4
69	4
70	4
71	4
72	4
21	4
22	4
23	4
24	4
25	4
26	4
1	4
2	4
3	4
4	4
5	4
6	4
7	4
8	4
9	4
13	4
17	4
27	4
28	4
31	4
39	4
40	4
42	4
43	4
44	4
45	4
48	4
49	4
52	4
53	4
54	4
55	4
56	4
57	4
58	4
73	4
78	4
60	5
61	5
62	5
63	5
64	5
65	5
66	5
67	5
68	5
69	5
70	5
71	5
72	5
21	5
22	5
23	5
24	5
25	5
26	5
1	5
2	5
3	5
4	5
5	5
6	5
7	5
8	5
9	5
10	5
11	5
12	5
13	5
14	5
15	5
16	5
17	5
18	5
19	5
20	5
27	5
28	5
29	5
30	5
31	5
32	5
33	5
34	5
35	5
36	5
37	5
38	5
39	5
40	5
41	5
42	5
43	5
44	5
45	5
46	5
48	5
49	5
50	5
52	5
53	5
54	5
55	5
56	5
57	5
58	5
59	5
73	5
78	5
82	5
83	5
60	6
61	6
62	6
63	6
64	6
65	6
66	6
67	6
68	6
69	6
70	6
71	6
72	6
21	6
22	6
23	6
24	6
25	6
26	6
1	6
2	6
3	6
4	6
5	6
6	6
7	6
8	6
9	6
10	6
11	6
12	6
13	6
14	6
15	6
16	6
17	6
18	6
19	6
20	6
27	6
28	6
29	6
30	6
31	6
32	6
33	6
34	6
35	6
36	6
37	6
38	6
39	6
40	6
41	6
42	6
43	6
44	6
45	6
46	6
48	6
49	6
50	6
52	6
53	6
54	6
55	6
56	6
57	6
58	6
59	6
73	6
78	6
82	6
83	6
60	7
61	7
62	7
63	7
64	7
65	7
66	7
67	7
68	7
69	7
70	7
71	7
72	7
21	7
22	7
23	7
24	7
25	7
26	7
1	7
2	7
3	7
4	7
5	7
6	7
7	7
8	7
9	7
10	7
11	7
12	7
13	7
14	7
15	7
16	7
17	7
18	7
19	7
20	7
27	7
28	7
29	7
30	7
31	7
32	7
33	7
34	7
35	7
36	7
37	7
38	7
39	7
40	7
41	7
42	7
43	7
44	7
45	7
46	7
48	7
49	7
50	7
52	7
53	7
54	7
55	7
56	7
57	7
58	7
59	7
73	7
78	7
82	7
83	7
60	8
61	8
62	8
63	8
64	8
65	8
66	8
67	8
68	8
69	8
70	8
71	8
72	8
21	8
22	8
23	8
24	8
25	8
26	8
1	8
2	8
3	8
4	8
5	8
6	8
7	8
8	8
9	8
10	8
11	8
12	8
13	8
14	8
15	8
16	8
17	8
18	8
19	8
20	8
27	8
28	8
29	8
30	8
31	8
32	8
33	8
34	8
35	8
36	8
37	8
38	8
39	8
40	8
41	8
42	8
43	8
44	8
45	8
46	8
48	8
49	8
50	8
52	8
53	8
54	8
55	8
56	8
57	8
58	8
59	8
73	8
78	8
82	8
83	8
60	9
64	9
72	9
21	9
1	9
5	9
9	9
13	9
17	9
27	9
31	9
35	9
36	9
39	9
42	9
45	9
48	9
52	9
53	9
54	9
55	9
56	9
57	9
58	9
59	9
82	9
72	10
27	10
28	10
39	10
40	10
72	11
21	11
22	11
23	11
1	11
5	11
9	11
13	11
27	11
28	11
39	11
52	11
53	11
54	11
55	11
56	11
57	11
58	11
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
1	super_admin	web	2026-03-05 10:08:52	2026-03-05 10:08:52
2	bendahara	web	2026-03-05 10:08:52	2026-03-05 10:08:52
3	kepala_sekolah	web	2026-03-05 10:08:53	2026-03-05 10:08:53
4	operator_tu	web	2026-03-05 10:08:53	2026-03-05 10:08:53
5	admin_tu	web	2026-03-05 10:08:53	2026-03-05 10:08:53
6	admin_tu_mi	web	2026-03-05 10:08:53	2026-03-05 10:08:53
7	admin_tu_ra	web	2026-03-05 10:08:53	2026-03-05 10:08:53
8	admin_tu_dta	web	2026-03-05 10:08:53	2026-03-05 10:08:53
9	auditor	web	2026-03-05 10:08:53	2026-03-05 10:08:53
10	cashier	web	2026-03-05 10:08:53	2026-03-05 10:08:53
11	staff	web	2026-03-05 10:08:56	2026-03-05 10:08:56
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.settings (id, key, value, type, "group", description, created_at, updated_at) FROM stdin;
1	dangerous_permanent_delete_enabled	false	boolean	system	Izinkan permanent delete superadmin	2026-03-05 10:08:51	2026-03-05 10:08:51
2	school_name	Madrasah Ibtidaiyah	string	school	Nama sekolah	2026-03-05 10:08:53	2026-03-05 10:08:53
3	school_address		string	school	Alamat sekolah	2026-03-05 10:08:53	2026-03-05 10:08:53
4	school_phone		string	school	Nomor telepon sekolah	2026-03-05 10:08:53	2026-03-05 10:08:53
5	school_logo		string	school	Path ke file logo	2026-03-05 10:08:53	2026-03-05 10:08:53
6	school_email		string	school	Email sekolah	2026-03-05 10:08:53	2026-03-05 10:08:53
7	receipt_footer_text	Terima kasih atas pembayarannya	string	receipt	Teks footer kwitansi	2026-03-05 10:08:53	2026-03-05 10:08:53
8	receipt_show_logo	true	boolean	receipt	Tampilkan logo di kwitansi	2026-03-05 10:08:53	2026-03-05 10:08:53
9	whatsapp_gateway_url		string	notification	URL gateway WhatsApp API	2026-03-05 10:08:53	2026-03-05 10:08:53
10	whatsapp_enabled	false	boolean	notification	Aktifkan notifikasi WhatsApp	2026-03-05 10:08:53	2026-03-05 10:08:53
11	notification_payment_template	Yth. Orang Tua/Wali {student_name}, pembayaran {fee_type} sebesar Rp {amount} telah diterima. Terima kasih.	string	notification	Template notifikasi pembayaran	2026-03-05 10:08:53	2026-03-05 10:08:53
12	notification_arrears_template	Yth. Orang Tua/Wali {student_name}, mengingatkan bahwa terdapat tunggakan {fee_type} sebesar Rp {amount}. Mohon segera melakukan pembayaran.	string	notification	Template notifikasi tunggakan	2026-03-05 10:08:53	2026-03-05 10:08:53
13	arrears_threshold_months	1	number	arrears	Batas bulan tunggakan sebelum reminder dikirim	2026-03-05 10:08:53	2026-03-05 10:08:53
14	academic_year_current	2025/2026	string	system	Tahun akademik aktif	2026-03-05 10:08:53	2026-03-05 10:08:53
15	inactivity_timeout	7200	number	system	Timeout inaktivitas sesi (detik)	2026-03-05 10:08:53	2026-03-05 10:08:53
16	school_name_mi	Madrasah Ibtidaiyah (MI)	string	school	Nama sekolah untuk unit MI	2026-03-05 10:08:53	2026-03-05 10:08:53
17	school_address_mi		string	school	Alamat sekolah untuk unit MI	2026-03-05 10:08:53	2026-03-05 10:08:53
18	school_phone_mi		string	school	Nomor telepon sekolah untuk unit MI	2026-03-05 10:08:53	2026-03-05 10:08:53
19	school_logo_mi		string	school	Path logo sekolah untuk unit MI	2026-03-05 10:08:53	2026-03-05 10:08:53
20	school_name_ra	Raudhatul Athfal (RA)	string	school	Nama sekolah untuk unit RA	2026-03-05 10:08:53	2026-03-05 10:08:53
21	school_address_ra		string	school	Alamat sekolah untuk unit RA	2026-03-05 10:08:53	2026-03-05 10:08:53
22	school_phone_ra		string	school	Nomor telepon sekolah untuk unit RA	2026-03-05 10:08:53	2026-03-05 10:08:53
23	school_logo_ra		string	school	Path logo sekolah untuk unit RA	2026-03-05 10:08:53	2026-03-05 10:08:53
24	school_name_dta	Diniyah Takmiliyah Awaliyah (DTA)	string	school	Nama sekolah untuk unit DTA	2026-03-05 10:08:53	2026-03-05 10:08:53
25	school_address_dta		string	school	Alamat sekolah untuk unit DTA	2026-03-05 10:08:53	2026-03-05 10:08:53
26	school_phone_dta		string	school	Nomor telepon sekolah untuk unit DTA	2026-03-05 10:08:53	2026-03-05 10:08:53
27	school_logo_dta		string	school	Path logo sekolah untuk unit DTA	2026-03-05 10:08:53	2026-03-05 10:08:53
\.


--
-- Data for Name: settlement_allocations; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.settlement_allocations (id, settlement_id, invoice_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: settlements; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.settlements (id, settlement_number, student_id, payment_date, payment_method, total_amount, allocated_amount, reference_number, notes, status, created_by, cancelled_at, cancelled_by, cancellation_reason, created_at, updated_at, unit_id, voided_at, voided_by, void_reason) FROM stdin;
\.


--
-- Data for Name: student_categories; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.student_categories (id, code, name, description, discount_percentage, created_at, updated_at, unit_id, deleted_at) FROM stdin;
\.


--
-- Data for Name: student_enrollments; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.student_enrollments (id, unit_id, student_id, academic_year_id, class_id, start_date, end_date, is_current, entry_status, exit_status, promotion_batch_id, previous_enrollment_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: student_fee_mappings; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.student_fee_mappings (id, unit_id, student_id, fee_matrix_id, effective_from, effective_to, is_active, notes, created_by, updated_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: student_obligations; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.student_obligations (id, student_id, fee_type_id, month, year, amount, is_paid, paid_amount, paid_at, transaction_item_id, created_at, updated_at, unit_id, academic_year_id, student_enrollment_id, class_id_snapshot) FROM stdin;
\.


--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.students (id, nis, nisn, name, class_id, category_id, gender, birth_date, birth_place, parent_name, parent_phone, parent_whatsapp, address, status, enrollment_date, created_at, updated_at, deleted_at, unit_id) FROM stdin;
\.


--
-- Data for Name: transaction_items; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.transaction_items (id, transaction_id, fee_type_id, description, amount, month, year, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.transactions (id, transaction_number, transaction_date, type, student_id, payment_method, total_amount, description, receipt_path, proof_path, status, cancelled_at, cancelled_by, cancellation_reason, created_by, created_at, updated_at, account_id, category_id, unit_id) FROM stdin;
\.


--
-- Data for Name: units; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.units (id, code, name, is_active, created_at, updated_at) FROM stdin;
1	MI	Madrasah Ibtidaiyah (MI)	t	2026-03-05 10:08:50	2026-03-05 10:08:50
2	RA	Raudhatul Athfal (RA)	t	2026-03-05 10:08:50	2026-03-05 10:08:50
3	DTA	Diniyah Takmiliyah Awaliyah (DTA)	t	2026-03-05 10:08:50	2026-03-05 10:08:50
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: sakumi_user
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, is_active, unit_id) FROM stdin;
2	Admin TU RA	admin.tu.ra@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	\N	2026-03-05 10:08:57	2026-03-05 11:28:26	t	2
3	Admin TU DTA	admin.tu.dta@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	\N	2026-03-05 10:08:57	2026-03-05 11:28:26	t	3
4	Staff	staff@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	\N	2026-03-05 10:08:58	2026-03-05 11:28:26	t	1
5	Bendahara	bendahara@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	\N	2026-03-05 10:08:59	2026-03-05 11:28:26	t	1
6	Kepala Sekolah	kepala.sekolah@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	\N	2026-03-05 10:08:59	2026-03-05 11:28:26	t	1
1	Admin TU MI	admin.tu.mi@sakumi.local	\N	$2y$12$4E.XuvOFKUHugqTLtSrtX.gOCrNRUVgqe760j8MhGMNL2/wp4zVfq	5wms5kBFjLOzLQDeakvFT5YqqMqaQSpQfI4V6qdxMQkbm0zabZR7ZDhLxyFB	2026-03-05 10:08:56	2026-03-05 11:28:26	t	1
\.


--
-- Name: academic_years_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.academic_years_id_seq', 1, false);


--
-- Name: account_mappings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.account_mappings_id_seq', 30, true);


--
-- Name: accounting_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.accounting_events_id_seq', 1, false);


--
-- Name: accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.accounts_id_seq', 1, false);


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 205, true);


--
-- Name: admission_period_quotas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.admission_period_quotas_id_seq', 1, false);


--
-- Name: admission_periods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.admission_periods_id_seq', 1, false);


--
-- Name: applicants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.applicants_id_seq', 1, false);


--
-- Name: bank_reconciliation_lines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.bank_reconciliation_lines_id_seq', 1, false);


--
-- Name: bank_reconciliation_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.bank_reconciliation_logs_id_seq', 1, false);


--
-- Name: bank_reconciliation_sessions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.bank_reconciliation_sessions_id_seq', 1, false);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.categories_id_seq', 1, false);


--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.chart_of_accounts_id_seq', 12, true);


--
-- Name: class_promotion_paths_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.class_promotion_paths_id_seq', 1, false);


--
-- Name: classes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.classes_id_seq', 1, false);


--
-- Name: document_sequences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.document_sequences_id_seq', 1, false);


--
-- Name: expense_budgets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.expense_budgets_id_seq', 1, false);


--
-- Name: expense_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.expense_entries_id_seq', 1, false);


--
-- Name: expense_fee_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.expense_fee_categories_id_seq', 36, true);


--
-- Name: expense_fee_subcategories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.expense_fee_subcategories_id_seq', 36, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: fee_matrix_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.fee_matrix_id_seq', 1, false);


--
-- Name: fee_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.fee_types_id_seq', 198, true);


--
-- Name: fiscal_periods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.fiscal_periods_id_seq', 1, false);


--
-- Name: invoice_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.invoice_items_id_seq', 1, false);


--
-- Name: invoices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.invoices_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: journal_entries_v2_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.journal_entries_v2_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 59, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.notifications_id_seq', 1, false);


--
-- Name: payment_allocations_v2_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.payment_allocations_v2_id_seq', 1, false);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.permissions_id_seq', 85, true);


--
-- Name: promotion_batch_students_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.promotion_batch_students_id_seq', 1, false);


--
-- Name: promotion_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.promotion_batches_id_seq', 1, false);


--
-- Name: receipt_print_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.receipt_print_logs_id_seq', 1, false);


--
-- Name: receipts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.receipts_id_seq', 1, false);


--
-- Name: reversals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.reversals_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.roles_id_seq', 11, true);


--
-- Name: settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.settings_id_seq', 27, true);


--
-- Name: settlement_allocations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.settlement_allocations_id_seq', 1, false);


--
-- Name: settlements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.settlements_id_seq', 1, false);


--
-- Name: student_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.student_categories_id_seq', 1, false);


--
-- Name: student_enrollments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.student_enrollments_id_seq', 1, false);


--
-- Name: student_fee_mappings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.student_fee_mappings_id_seq', 1, false);


--
-- Name: student_obligations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.student_obligations_id_seq', 1, false);


--
-- Name: students_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.students_id_seq', 1, false);


--
-- Name: transaction_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.transaction_items_id_seq', 1, false);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.transactions_id_seq', 1, false);


--
-- Name: units_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.units_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- Name: academic_years academic_years_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_pkey PRIMARY KEY (id);


--
-- Name: academic_years academic_years_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: account_mappings account_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.account_mappings
    ADD CONSTRAINT account_mappings_pkey PRIMARY KEY (id);


--
-- Name: account_mappings account_mappings_unique_rule; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.account_mappings
    ADD CONSTRAINT account_mappings_unique_rule UNIQUE (unit_id, event_type, line_key, entry_side, priority);


--
-- Name: accounting_events accounting_events_event_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_event_uuid_unique UNIQUE (event_uuid);


--
-- Name: accounting_events accounting_events_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: accounting_events accounting_events_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: accounts accounts_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: admission_period_quotas admission_period_quotas_admission_period_id_class_id_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas
    ADD CONSTRAINT admission_period_quotas_admission_period_id_class_id_unique UNIQUE (admission_period_id, class_id);


--
-- Name: admission_period_quotas admission_period_quotas_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas
    ADD CONSTRAINT admission_period_quotas_pkey PRIMARY KEY (id);


--
-- Name: admission_periods admission_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_periods
    ADD CONSTRAINT admission_periods_pkey PRIMARY KEY (id);


--
-- Name: applicants applicants_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_pkey PRIMARY KEY (id);


--
-- Name: applicants applicants_registration_number_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_registration_number_unique UNIQUE (registration_number);


--
-- Name: bank_reconciliation_lines bank_reconciliation_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_lines
    ADD CONSTRAINT bank_reconciliation_lines_pkey PRIMARY KEY (id);


--
-- Name: bank_reconciliation_logs bank_reconciliation_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_logs
    ADD CONSTRAINT bank_reconciliation_logs_pkey PRIMARY KEY (id);


--
-- Name: bank_reconciliation_sessions bank_reconciliation_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions
    ADD CONSTRAINT bank_reconciliation_sessions_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: chart_of_accounts chart_of_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_pkey PRIMARY KEY (id);


--
-- Name: chart_of_accounts chart_of_accounts_unit_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_unit_id_code_unique UNIQUE (unit_id, code);


--
-- Name: class_promotion_paths class_promo_paths_window_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promo_paths_window_unique UNIQUE (from_class_id, to_class_id, from_academic_year_id, to_academic_year_id);


--
-- Name: class_promotion_paths class_promotion_paths_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_pkey PRIMARY KEY (id);


--
-- Name: classes classes_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.classes
    ADD CONSTRAINT classes_pkey PRIMARY KEY (id);


--
-- Name: classes classes_unit_name_ay_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.classes
    ADD CONSTRAINT classes_unit_name_ay_unique UNIQUE (unit_id, name, academic_year);


--
-- Name: document_sequences document_sequences_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.document_sequences
    ADD CONSTRAINT document_sequences_pkey PRIMARY KEY (id);


--
-- Name: document_sequences document_sequences_prefix_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.document_sequences
    ADD CONSTRAINT document_sequences_prefix_unique UNIQUE (prefix);


--
-- Name: expense_budgets expense_budgets_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT expense_budgets_pkey PRIMARY KEY (id);


--
-- Name: expense_entries expense_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_pkey PRIMARY KEY (id);


--
-- Name: expense_fee_categories expense_fee_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_categories
    ADD CONSTRAINT expense_fee_categories_pkey PRIMARY KEY (id);


--
-- Name: expense_fee_categories expense_fee_categories_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_categories
    ADD CONSTRAINT expense_fee_categories_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: expense_fee_subcategories expense_fee_subcategories_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_subcategories
    ADD CONSTRAINT expense_fee_subcategories_pkey PRIMARY KEY (id);


--
-- Name: expense_fee_subcategories expense_fee_subcategories_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_subcategories
    ADD CONSTRAINT expense_fee_subcategories_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fee_matrix fee_matrix_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT fee_matrix_pkey PRIMARY KEY (id);


--
-- Name: fee_types fee_types_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_types
    ADD CONSTRAINT fee_types_pkey PRIMARY KEY (id);


--
-- Name: fee_types fee_types_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_types
    ADD CONSTRAINT fee_types_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: fiscal_periods fiscal_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fiscal_periods
    ADD CONSTRAINT fiscal_periods_pkey PRIMARY KEY (id);


--
-- Name: fiscal_periods fiscal_periods_unit_id_period_key_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fiscal_periods
    ADD CONSTRAINT fiscal_periods_unit_id_period_key_unique UNIQUE (unit_id, period_key);


--
-- Name: invoice_items invoice_items_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: journal_entries_v2 journal_entries_v2_accounting_event_id_line_no_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2
    ADD CONSTRAINT journal_entries_v2_accounting_event_id_line_no_unique UNIQUE (accounting_event_id, line_no);


--
-- Name: journal_entries_v2 journal_entries_v2_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2
    ADD CONSTRAINT journal_entries_v2_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_allocations_v2 payment_allocations_v2_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.payment_allocations_v2
    ADD CONSTRAINT payment_allocations_v2_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: promotion_batch_students promotion_batch_students_batch_student_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_batch_student_unique UNIQUE (promotion_batch_id, student_id);


--
-- Name: promotion_batch_students promotion_batch_students_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_pkey PRIMARY KEY (id);


--
-- Name: promotion_batches promotion_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_pkey PRIMARY KEY (id);


--
-- Name: promotion_batches promotion_batches_unit_window_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_unit_window_unique UNIQUE (unit_id, from_academic_year_id, to_academic_year_id);


--
-- Name: receipt_print_logs receipt_print_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipt_print_logs
    ADD CONSTRAINT receipt_print_logs_pkey PRIMARY KEY (id);


--
-- Name: receipts receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_pkey PRIMARY KEY (id);


--
-- Name: receipts receipts_settlement_id_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_settlement_id_unique UNIQUE (settlement_id);


--
-- Name: receipts receipts_transaction_id_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_transaction_id_unique UNIQUE (transaction_id);


--
-- Name: receipts receipts_verification_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_verification_code_unique UNIQUE (verification_code);


--
-- Name: reversals reversals_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_pkey PRIMARY KEY (id);


--
-- Name: reversals reversals_reversal_accounting_event_id_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_reversal_accounting_event_id_unique UNIQUE (reversal_accounting_event_id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_key_unique UNIQUE (key);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: settlement_allocations settlement_allocations_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlement_allocations
    ADD CONSTRAINT settlement_allocations_pkey PRIMARY KEY (id);


--
-- Name: settlements settlements_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_pkey PRIMARY KEY (id);


--
-- Name: student_categories student_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_categories
    ADD CONSTRAINT student_categories_pkey PRIMARY KEY (id);


--
-- Name: student_categories student_categories_unit_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_categories
    ADD CONSTRAINT student_categories_unit_code_unique UNIQUE (unit_id, code);


--
-- Name: student_enrollments student_enrollments_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_pkey PRIMARY KEY (id);


--
-- Name: student_enrollments student_enrollments_student_ay_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_student_ay_unique UNIQUE (student_id, academic_year_id);


--
-- Name: student_fee_mappings student_fee_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_pkey PRIMARY KEY (id);


--
-- Name: student_obligations student_obligations_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_pkey PRIMARY KEY (id);


--
-- Name: students students_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_pkey PRIMARY KEY (id);


--
-- Name: students students_unit_nis_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_unit_nis_unique UNIQUE (unit_id, nis);


--
-- Name: students students_unit_nisn_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_unit_nisn_unique UNIQUE (unit_id, nisn);


--
-- Name: transaction_items transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_transaction_number_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_number_unique UNIQUE (transaction_number);


--
-- Name: units units_code_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_code_unique UNIQUE (code);


--
-- Name: units units_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.units
    ADD CONSTRAINT units_pkey PRIMARY KEY (id);


--
-- Name: bank_reconciliation_sessions uq_bank_recon_session; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions
    ADD CONSTRAINT uq_bank_recon_session UNIQUE (unit_id, bank_account_name, period_year, period_month);


--
-- Name: expense_budgets uq_expense_budget_period; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT uq_expense_budget_period UNIQUE (unit_id, year, month, expense_fee_subcategory_id);


--
-- Name: fee_matrix uq_fee_matrix_unit_class_cat_fee_effective; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT uq_fee_matrix_unit_class_cat_fee_effective UNIQUE (unit_id, class_id, category_id, fee_type_id, effective_from);


--
-- Name: invoice_items uq_invoice_items_invoice_obligation; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT uq_invoice_items_invoice_obligation UNIQUE (invoice_id, student_obligation_id);


--
-- Name: invoices uq_invoices_unit_number; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT uq_invoices_unit_number UNIQUE (unit_id, invoice_number);


--
-- Name: student_obligations uq_obligation_period; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT uq_obligation_period UNIQUE (student_id, fee_type_id, month, year);


--
-- Name: settlement_allocations uq_settlement_allocations_settlement_invoice; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlement_allocations
    ADD CONSTRAINT uq_settlement_allocations_settlement_invoice UNIQUE (settlement_id, invoice_id);


--
-- Name: settlements uq_settlements_unit_number; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT uq_settlements_unit_number UNIQUE (unit_id, settlement_number);


--
-- Name: student_fee_mappings uq_sfm_student_matrix_from; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT uq_sfm_student_matrix_from UNIQUE (student_id, fee_matrix_id, effective_from);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: academic_years_unit_active_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX academic_years_unit_active_idx ON public.academic_years USING btree (unit_id, is_active);


--
-- Name: account_mappings_unit_id_account_code_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX account_mappings_unit_id_account_code_index ON public.account_mappings USING btree (unit_id, account_code);


--
-- Name: account_mappings_unit_id_event_type_is_active_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX account_mappings_unit_id_event_type_is_active_index ON public.account_mappings USING btree (unit_id, event_type, is_active);


--
-- Name: accounting_events_unit_id_event_type_effective_date_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX accounting_events_unit_id_event_type_effective_date_index ON public.accounting_events USING btree (unit_id, event_type, effective_date);


--
-- Name: accounting_events_unit_id_source_type_source_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX accounting_events_unit_id_source_type_source_id_index ON public.accounting_events USING btree (unit_id, source_type, source_id);


--
-- Name: accounting_events_unit_id_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX accounting_events_unit_id_status_index ON public.accounting_events USING btree (unit_id, status);


--
-- Name: accounts_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX accounts_type_index ON public.accounts USING btree (type);


--
-- Name: accounts_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX accounts_unit_id_index ON public.accounts USING btree (unit_id);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: admission_periods_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX admission_periods_status_index ON public.admission_periods USING btree (status);


--
-- Name: admission_periods_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX admission_periods_unit_id_index ON public.admission_periods USING btree (unit_id);


--
-- Name: applicants_admission_period_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX applicants_admission_period_id_index ON public.applicants USING btree (admission_period_id);


--
-- Name: applicants_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX applicants_status_index ON public.applicants USING btree (status);


--
-- Name: applicants_target_class_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX applicants_target_class_id_index ON public.applicants USING btree (target_class_id);


--
-- Name: applicants_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX applicants_unit_id_index ON public.applicants USING btree (unit_id);


--
-- Name: categories_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX categories_type_index ON public.categories USING btree (type);


--
-- Name: categories_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX categories_unit_id_index ON public.categories USING btree (unit_id);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: chart_of_accounts_unit_id_is_active_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX chart_of_accounts_unit_id_is_active_index ON public.chart_of_accounts USING btree (unit_id, is_active);


--
-- Name: chart_of_accounts_unit_id_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX chart_of_accounts_unit_id_type_index ON public.chart_of_accounts USING btree (unit_id, type);


--
-- Name: class_promo_paths_lookup_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX class_promo_paths_lookup_idx ON public.class_promotion_paths USING btree (unit_id, from_class_id, is_active);


--
-- Name: classes_unit_ay_level_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX classes_unit_ay_level_idx ON public.classes USING btree (unit_id, academic_year_id, level);


--
-- Name: classes_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX classes_unit_id_index ON public.classes USING btree (unit_id);


--
-- Name: expense_fee_categories_unit_sort_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX expense_fee_categories_unit_sort_idx ON public.expense_fee_categories USING btree (unit_id, sort_order);


--
-- Name: expense_fee_subcategories_unit_cat_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX expense_fee_subcategories_unit_cat_idx ON public.expense_fee_subcategories USING btree (unit_id, expense_fee_category_id);


--
-- Name: fee_matrix_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX fee_matrix_unit_id_index ON public.fee_matrix USING btree (unit_id);


--
-- Name: fee_types_unit_exp_subcat_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX fee_types_unit_exp_subcat_idx ON public.fee_types USING btree (unit_id, expense_fee_subcategory_id);


--
-- Name: fee_types_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX fee_types_unit_id_index ON public.fee_types USING btree (unit_id);


--
-- Name: fiscal_periods_unit_id_is_locked_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX fiscal_periods_unit_id_is_locked_index ON public.fiscal_periods USING btree (unit_id, is_locked);


--
-- Name: fiscal_periods_unit_id_starts_on_ends_on_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX fiscal_periods_unit_id_starts_on_ends_on_index ON public.fiscal_periods USING btree (unit_id, starts_on, ends_on);


--
-- Name: idx_activity_log_causer; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_activity_log_causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: idx_activity_log_created; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_activity_log_created ON public.activity_log USING btree (created_at);


--
-- Name: idx_bank_recon_lines_lookup; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_bank_recon_lines_lookup ON public.bank_reconciliation_lines USING btree (line_date, amount);


--
-- Name: idx_bank_recon_lines_status; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_bank_recon_lines_status ON public.bank_reconciliation_lines USING btree (bank_reconciliation_session_id, match_status);


--
-- Name: idx_bank_recon_logs_session_time; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_bank_recon_logs_session_time ON public.bank_reconciliation_logs USING btree (bank_reconciliation_session_id, created_at);


--
-- Name: idx_bank_recon_session_period; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_bank_recon_session_period ON public.bank_reconciliation_sessions USING btree (unit_id, period_year, period_month, status);


--
-- Name: idx_expense_budget_period; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_expense_budget_period ON public.expense_budgets USING btree (unit_id, year, month);


--
-- Name: idx_expense_entries_period_status; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_expense_entries_period_status ON public.expense_entries USING btree (unit_id, entry_date, status);


--
-- Name: idx_expense_entries_subcat_period; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_expense_entries_subcat_period ON public.expense_entries USING btree (unit_id, expense_fee_subcategory_id, entry_date);


--
-- Name: idx_feematrix_lookup; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_feematrix_lookup ON public.fee_matrix USING btree (fee_type_id, class_id, category_id, effective_from);


--
-- Name: idx_invoices_due_date_status; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_invoices_due_date_status ON public.invoices USING btree (due_date, status);


--
-- Name: idx_obligations_unpaid; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_obligations_unpaid ON public.student_obligations USING btree (student_id, is_paid) WHERE (is_paid = false);


--
-- Name: idx_settlement_alloc_invoice_amount; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_settlement_alloc_invoice_amount ON public.settlement_allocations USING btree (invoice_id, amount);


--
-- Name: idx_settlements_payment_date_status; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_settlements_payment_date_status ON public.settlements USING btree (payment_date, status);


--
-- Name: idx_sfm_matrix_effective; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_sfm_matrix_effective ON public.student_fee_mappings USING btree (fee_matrix_id, effective_from, effective_to);


--
-- Name: idx_sfm_student_effective; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_sfm_student_effective ON public.student_fee_mappings USING btree (student_id, effective_from, effective_to, is_active);


--
-- Name: idx_transactions_date_status_type; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX idx_transactions_date_status_type ON public.transactions USING btree (transaction_date, status, type);


--
-- Name: invoice_items_invoice_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoice_items_invoice_id_index ON public.invoice_items USING btree (invoice_id);


--
-- Name: invoice_items_student_obligation_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoice_items_student_obligation_id_index ON public.invoice_items USING btree (student_obligation_id);


--
-- Name: invoices_ay_enrollment_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_ay_enrollment_idx ON public.invoices USING btree (academic_year_id, student_enrollment_id);


--
-- Name: invoices_invoice_date_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_invoice_date_index ON public.invoices USING btree (invoice_date);


--
-- Name: invoices_period_type_period_identifier_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_period_type_period_identifier_index ON public.invoices USING btree (period_type, period_identifier);


--
-- Name: invoices_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_status_index ON public.invoices USING btree (status);


--
-- Name: invoices_student_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_student_id_index ON public.invoices USING btree (student_id);


--
-- Name: invoices_unit_due_date_status_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_unit_due_date_status_idx ON public.invoices USING btree (unit_id, due_date, status);


--
-- Name: invoices_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX invoices_unit_id_index ON public.invoices USING btree (unit_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: journal_entries_v2_unit_id_account_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX journal_entries_v2_unit_id_account_id_index ON public.journal_entries_v2 USING btree (unit_id, account_id);


--
-- Name: journal_entries_v2_unit_id_entry_date_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX journal_entries_v2_unit_id_entry_date_index ON public.journal_entries_v2 USING btree (unit_id, entry_date);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: mv_ar_outstanding_due; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX mv_ar_outstanding_due ON public.mv_ar_outstanding USING btree (due_date);


--
-- Name: mv_ar_outstanding_pk; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE UNIQUE INDEX mv_ar_outstanding_pk ON public.mv_ar_outstanding USING btree (invoice_id);


--
-- Name: mv_ar_outstanding_student; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX mv_ar_outstanding_student ON public.mv_ar_outstanding USING btree (student_id);


--
-- Name: mv_ar_outstanding_unit; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX mv_ar_outstanding_unit ON public.mv_ar_outstanding USING btree (unit_id);


--
-- Name: mv_daily_cash_pk; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE UNIQUE INDEX mv_daily_cash_pk ON public.mv_daily_cash_summary USING btree (entry_date, unit_id);


--
-- Name: mv_daily_cash_unit; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX mv_daily_cash_unit ON public.mv_daily_cash_summary USING btree (unit_id);


--
-- Name: notifications_student_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX notifications_student_id_index ON public.notifications USING btree (student_id);


--
-- Name: notifications_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX notifications_unit_id_index ON public.notifications USING btree (unit_id);


--
-- Name: notifications_whatsapp_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX notifications_whatsapp_status_index ON public.notifications USING btree (whatsapp_status);


--
-- Name: payment_allocations_v2_accounting_event_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX payment_allocations_v2_accounting_event_id_index ON public.payment_allocations_v2 USING btree (accounting_event_id);


--
-- Name: payment_allocations_v2_unit_id_invoice_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX payment_allocations_v2_unit_id_invoice_id_index ON public.payment_allocations_v2 USING btree (unit_id, invoice_id);


--
-- Name: payment_allocations_v2_unit_id_payment_source_type_payment_sour; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX payment_allocations_v2_unit_id_payment_source_type_payment_sour ON public.payment_allocations_v2 USING btree (unit_id, payment_source_type, payment_source_id);


--
-- Name: promotion_batch_students_batch_action_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX promotion_batch_students_batch_action_idx ON public.promotion_batch_students USING btree (promotion_batch_id, action);


--
-- Name: promotion_batches_unit_status_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX promotion_batches_unit_status_idx ON public.promotion_batches USING btree (unit_id, status);


--
-- Name: receipt_print_logs_receipt_id_printed_at_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX receipt_print_logs_receipt_id_printed_at_index ON public.receipt_print_logs USING btree (receipt_id, printed_at);


--
-- Name: receipts_invoice_id_issued_at_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX receipts_invoice_id_issued_at_index ON public.receipts USING btree (invoice_id, issued_at);


--
-- Name: reversals_unit_id_original_accounting_event_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX reversals_unit_id_original_accounting_event_id_index ON public.reversals USING btree (unit_id, original_accounting_event_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settlement_allocations_invoice_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlement_allocations_invoice_id_index ON public.settlement_allocations USING btree (invoice_id);


--
-- Name: settlement_allocations_invoice_settlement_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlement_allocations_invoice_settlement_idx ON public.settlement_allocations USING btree (invoice_id, settlement_id);


--
-- Name: settlement_allocations_settlement_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlement_allocations_settlement_id_index ON public.settlement_allocations USING btree (settlement_id);


--
-- Name: settlements_payment_date_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlements_payment_date_index ON public.settlements USING btree (payment_date);


--
-- Name: settlements_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlements_status_index ON public.settlements USING btree (status);


--
-- Name: settlements_student_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlements_student_id_index ON public.settlements USING btree (student_id);


--
-- Name: settlements_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlements_unit_id_index ON public.settlements USING btree (unit_id);


--
-- Name: settlements_unit_status_payment_date_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX settlements_unit_status_payment_date_idx ON public.settlements USING btree (unit_id, status, payment_date);


--
-- Name: student_categories_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_categories_unit_id_index ON public.student_categories USING btree (unit_id);


--
-- Name: student_enrollments_unit_ay_class_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_enrollments_unit_ay_class_idx ON public.student_enrollments USING btree (unit_id, academic_year_id, class_id);


--
-- Name: student_enrollments_unit_student_current_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_enrollments_unit_student_current_idx ON public.student_enrollments USING btree (unit_id, student_id, is_current);


--
-- Name: student_obligations_ay_class_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_obligations_ay_class_idx ON public.student_obligations USING btree (academic_year_id, class_id_snapshot);


--
-- Name: student_obligations_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_obligations_unit_id_index ON public.student_obligations USING btree (unit_id);


--
-- Name: student_obligations_year_month_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX student_obligations_year_month_index ON public.student_obligations USING btree (year, month);


--
-- Name: students_class_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX students_class_id_index ON public.students USING btree (class_id);


--
-- Name: students_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX students_status_index ON public.students USING btree (status);


--
-- Name: students_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX students_unit_id_index ON public.students USING btree (unit_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: transaction_items_transaction_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transaction_items_transaction_id_index ON public.transaction_items USING btree (transaction_id);


--
-- Name: transactions_account_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_account_id_index ON public.transactions USING btree (account_id);


--
-- Name: transactions_category_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_category_id_index ON public.transactions USING btree (category_id);


--
-- Name: transactions_student_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_student_id_index ON public.transactions USING btree (student_id);


--
-- Name: transactions_transaction_date_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_transaction_date_index ON public.transactions USING btree (transaction_date);


--
-- Name: transactions_type_status_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_type_status_index ON public.transactions USING btree (type, status);


--
-- Name: transactions_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_unit_id_index ON public.transactions USING btree (unit_id);


--
-- Name: transactions_unit_status_type_date_idx; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX transactions_unit_status_type_date_idx ON public.transactions USING btree (unit_id, status, type, transaction_date);


--
-- Name: users_unit_id_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX users_unit_id_index ON public.users USING btree (unit_id);


--
-- Name: invoices check_invoice_immutability; Type: TRIGGER; Schema: public; Owner: sakumi_user
--

CREATE TRIGGER check_invoice_immutability BEFORE UPDATE ON public.invoices FOR EACH ROW EXECUTE FUNCTION public.prevent_invoice_update();


--
-- Name: settlement_allocations check_invoice_over_settlement; Type: TRIGGER; Schema: public; Owner: sakumi_user
--

CREATE TRIGGER check_invoice_over_settlement BEFORE INSERT OR UPDATE ON public.settlement_allocations FOR EACH ROW EXECUTE FUNCTION public.prevent_invoice_over_settlement();


--
-- Name: settlements check_settlement_immutability; Type: TRIGGER; Schema: public; Owner: sakumi_user
--

CREATE TRIGGER check_settlement_immutability BEFORE UPDATE ON public.settlements FOR EACH ROW EXECUTE FUNCTION public.prevent_settlement_update();


--
-- Name: transactions check_transaction_immutability; Type: TRIGGER; Schema: public; Owner: sakumi_user
--

CREATE TRIGGER check_transaction_immutability BEFORE UPDATE ON public.transactions FOR EACH ROW EXECUTE FUNCTION public.prevent_transaction_update();


--
-- Name: academic_years academic_years_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: account_mappings account_mappings_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.account_mappings
    ADD CONSTRAINT account_mappings_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: accounting_events accounting_events_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: accounting_events accounting_events_fiscal_period_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_fiscal_period_id_foreign FOREIGN KEY (fiscal_period_id) REFERENCES public.fiscal_periods(id) ON DELETE SET NULL;


--
-- Name: accounting_events accounting_events_reversal_of_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_reversal_of_event_id_foreign FOREIGN KEY (reversal_of_event_id) REFERENCES public.accounting_events(id) ON DELETE SET NULL;


--
-- Name: accounting_events accounting_events_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounting_events
    ADD CONSTRAINT accounting_events_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: accounts accounts_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: admission_period_quotas admission_period_quotas_admission_period_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas
    ADD CONSTRAINT admission_period_quotas_admission_period_id_foreign FOREIGN KEY (admission_period_id) REFERENCES public.admission_periods(id) ON DELETE CASCADE;


--
-- Name: admission_period_quotas admission_period_quotas_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas
    ADD CONSTRAINT admission_period_quotas_class_id_foreign FOREIGN KEY (class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: admission_period_quotas admission_period_quotas_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_period_quotas
    ADD CONSTRAINT admission_period_quotas_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: admission_periods admission_periods_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.admission_periods
    ADD CONSTRAINT admission_periods_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: applicants applicants_admission_period_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_admission_period_id_foreign FOREIGN KEY (admission_period_id) REFERENCES public.admission_periods(id) ON DELETE RESTRICT;


--
-- Name: applicants applicants_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.student_categories(id) ON DELETE RESTRICT;


--
-- Name: applicants applicants_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: applicants applicants_status_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_status_changed_by_foreign FOREIGN KEY (status_changed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: applicants applicants_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE SET NULL;


--
-- Name: applicants applicants_target_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_target_class_id_foreign FOREIGN KEY (target_class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: applicants applicants_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.applicants
    ADD CONSTRAINT applicants_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: bank_reconciliation_lines bank_reconciliation_lines_bank_reconciliation_session_id_foreig; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_lines
    ADD CONSTRAINT bank_reconciliation_lines_bank_reconciliation_session_id_foreig FOREIGN KEY (bank_reconciliation_session_id) REFERENCES public.bank_reconciliation_sessions(id) ON DELETE CASCADE;


--
-- Name: bank_reconciliation_lines bank_reconciliation_lines_matched_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_lines
    ADD CONSTRAINT bank_reconciliation_lines_matched_by_foreign FOREIGN KEY (matched_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bank_reconciliation_lines bank_reconciliation_lines_matched_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_lines
    ADD CONSTRAINT bank_reconciliation_lines_matched_transaction_id_foreign FOREIGN KEY (matched_transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: bank_reconciliation_logs bank_reconciliation_logs_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_logs
    ADD CONSTRAINT bank_reconciliation_logs_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bank_reconciliation_logs bank_reconciliation_logs_bank_reconciliation_session_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_logs
    ADD CONSTRAINT bank_reconciliation_logs_bank_reconciliation_session_id_foreign FOREIGN KEY (bank_reconciliation_session_id) REFERENCES public.bank_reconciliation_sessions(id) ON DELETE CASCADE;


--
-- Name: bank_reconciliation_sessions bank_reconciliation_sessions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions
    ADD CONSTRAINT bank_reconciliation_sessions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bank_reconciliation_sessions bank_reconciliation_sessions_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions
    ADD CONSTRAINT bank_reconciliation_sessions_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: bank_reconciliation_sessions bank_reconciliation_sessions_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.bank_reconciliation_sessions
    ADD CONSTRAINT bank_reconciliation_sessions_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: categories categories_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: chart_of_accounts chart_of_accounts_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.chart_of_accounts(id) ON DELETE SET NULL;


--
-- Name: chart_of_accounts chart_of_accounts_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: class_promotion_paths class_promotion_paths_from_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_from_academic_year_id_foreign FOREIGN KEY (from_academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: class_promotion_paths class_promotion_paths_from_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_from_class_id_foreign FOREIGN KEY (from_class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: class_promotion_paths class_promotion_paths_to_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_to_academic_year_id_foreign FOREIGN KEY (to_academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: class_promotion_paths class_promotion_paths_to_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_to_class_id_foreign FOREIGN KEY (to_class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: class_promotion_paths class_promotion_paths_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.class_promotion_paths
    ADD CONSTRAINT class_promotion_paths_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: classes classes_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.classes
    ADD CONSTRAINT classes_academic_year_id_foreign FOREIGN KEY (academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: classes classes_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.classes
    ADD CONSTRAINT classes_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: expense_budgets expense_budgets_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT expense_budgets_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expense_budgets expense_budgets_expense_fee_subcategory_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT expense_budgets_expense_fee_subcategory_id_foreign FOREIGN KEY (expense_fee_subcategory_id) REFERENCES public.expense_fee_subcategories(id) ON DELETE RESTRICT;


--
-- Name: expense_budgets expense_budgets_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT expense_budgets_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: expense_budgets expense_budgets_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_budgets
    ADD CONSTRAINT expense_budgets_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expense_entries expense_entries_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expense_entries expense_entries_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expense_entries expense_entries_expense_fee_subcategory_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_expense_fee_subcategory_id_foreign FOREIGN KEY (expense_fee_subcategory_id) REFERENCES public.expense_fee_subcategories(id) ON DELETE RESTRICT;


--
-- Name: expense_entries expense_entries_fee_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_fee_type_id_foreign FOREIGN KEY (fee_type_id) REFERENCES public.fee_types(id) ON DELETE RESTRICT;


--
-- Name: expense_entries expense_entries_posted_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_posted_transaction_id_foreign FOREIGN KEY (posted_transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: expense_entries expense_entries_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: expense_entries expense_entries_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_entries
    ADD CONSTRAINT expense_entries_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: expense_fee_categories expense_fee_categories_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_categories
    ADD CONSTRAINT expense_fee_categories_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: expense_fee_subcategories expense_fee_subcategories_expense_fee_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_subcategories
    ADD CONSTRAINT expense_fee_subcategories_expense_fee_category_id_foreign FOREIGN KEY (expense_fee_category_id) REFERENCES public.expense_fee_categories(id) ON DELETE RESTRICT;


--
-- Name: expense_fee_subcategories expense_fee_subcategories_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.expense_fee_subcategories
    ADD CONSTRAINT expense_fee_subcategories_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: fee_matrix fee_matrix_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT fee_matrix_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.student_categories(id);


--
-- Name: fee_matrix fee_matrix_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT fee_matrix_class_id_foreign FOREIGN KEY (class_id) REFERENCES public.classes(id);


--
-- Name: fee_matrix fee_matrix_fee_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT fee_matrix_fee_type_id_foreign FOREIGN KEY (fee_type_id) REFERENCES public.fee_types(id);


--
-- Name: fee_matrix fee_matrix_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_matrix
    ADD CONSTRAINT fee_matrix_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: fee_types fee_types_expense_fee_subcategory_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_types
    ADD CONSTRAINT fee_types_expense_fee_subcategory_id_foreign FOREIGN KEY (expense_fee_subcategory_id) REFERENCES public.expense_fee_subcategories(id) ON DELETE SET NULL;


--
-- Name: fee_types fee_types_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fee_types
    ADD CONSTRAINT fee_types_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: fiscal_periods fiscal_periods_locked_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fiscal_periods
    ADD CONSTRAINT fiscal_periods_locked_by_foreign FOREIGN KEY (locked_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: fiscal_periods fiscal_periods_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.fiscal_periods
    ADD CONSTRAINT fiscal_periods_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: invoice_items invoice_items_fee_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_fee_type_id_foreign FOREIGN KEY (fee_type_id) REFERENCES public.fee_types(id) ON DELETE RESTRICT;


--
-- Name: invoice_items invoice_items_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoice_items invoice_items_student_obligation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoice_items
    ADD CONSTRAINT invoice_items_student_obligation_id_foreign FOREIGN KEY (student_obligation_id) REFERENCES public.student_obligations(id) ON DELETE RESTRICT;


--
-- Name: invoices invoices_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_academic_year_id_foreign FOREIGN KEY (academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: invoices invoices_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: invoices invoices_student_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_student_enrollment_id_foreign FOREIGN KEY (student_enrollment_id) REFERENCES public.student_enrollments(id) ON DELETE RESTRICT;


--
-- Name: invoices invoices_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE RESTRICT;


--
-- Name: invoices invoices_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: journal_entries_v2 journal_entries_v2_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2
    ADD CONSTRAINT journal_entries_v2_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.chart_of_accounts(id) ON DELETE RESTRICT;


--
-- Name: journal_entries_v2 journal_entries_v2_accounting_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2
    ADD CONSTRAINT journal_entries_v2_accounting_event_id_foreign FOREIGN KEY (accounting_event_id) REFERENCES public.accounting_events(id) ON DELETE RESTRICT;


--
-- Name: journal_entries_v2 journal_entries_v2_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.journal_entries_v2
    ADD CONSTRAINT journal_entries_v2_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id);


--
-- Name: notifications notifications_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: payment_allocations_v2 payment_allocations_v2_accounting_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.payment_allocations_v2
    ADD CONSTRAINT payment_allocations_v2_accounting_event_id_foreign FOREIGN KEY (accounting_event_id) REFERENCES public.accounting_events(id) ON DELETE RESTRICT;


--
-- Name: payment_allocations_v2 payment_allocations_v2_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.payment_allocations_v2
    ADD CONSTRAINT payment_allocations_v2_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE RESTRICT;


--
-- Name: payment_allocations_v2 payment_allocations_v2_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.payment_allocations_v2
    ADD CONSTRAINT payment_allocations_v2_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: promotion_batch_students promotion_batch_students_from_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_from_enrollment_id_foreign FOREIGN KEY (from_enrollment_id) REFERENCES public.student_enrollments(id) ON DELETE RESTRICT;


--
-- Name: promotion_batch_students promotion_batch_students_promotion_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_promotion_batch_id_foreign FOREIGN KEY (promotion_batch_id) REFERENCES public.promotion_batches(id) ON DELETE CASCADE;


--
-- Name: promotion_batch_students promotion_batch_students_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE RESTRICT;


--
-- Name: promotion_batch_students promotion_batch_students_to_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batch_students
    ADD CONSTRAINT promotion_batch_students_to_class_id_foreign FOREIGN KEY (to_class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: promotion_batches promotion_batches_applied_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_applied_by_foreign FOREIGN KEY (applied_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: promotion_batches promotion_batches_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: promotion_batches promotion_batches_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: promotion_batches promotion_batches_from_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_from_academic_year_id_foreign FOREIGN KEY (from_academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: promotion_batches promotion_batches_to_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_to_academic_year_id_foreign FOREIGN KEY (to_academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: promotion_batches promotion_batches_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.promotion_batches
    ADD CONSTRAINT promotion_batches_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: receipt_print_logs receipt_print_logs_receipt_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipt_print_logs
    ADD CONSTRAINT receipt_print_logs_receipt_id_foreign FOREIGN KEY (receipt_id) REFERENCES public.receipts(id) ON DELETE CASCADE;


--
-- Name: receipt_print_logs receipt_print_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipt_print_logs
    ADD CONSTRAINT receipt_print_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: receipts receipts_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: receipts receipts_settlement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_settlement_id_foreign FOREIGN KEY (settlement_id) REFERENCES public.settlements(id) ON DELETE SET NULL;


--
-- Name: receipts receipts_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.receipts
    ADD CONSTRAINT receipts_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: reversals reversals_original_accounting_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_original_accounting_event_id_foreign FOREIGN KEY (original_accounting_event_id) REFERENCES public.accounting_events(id) ON DELETE RESTRICT;


--
-- Name: reversals reversals_reversal_accounting_event_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_reversal_accounting_event_id_foreign FOREIGN KEY (reversal_accounting_event_id) REFERENCES public.accounting_events(id) ON DELETE RESTRICT;


--
-- Name: reversals reversals_reversed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_reversed_by_foreign FOREIGN KEY (reversed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: reversals reversals_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.reversals
    ADD CONSTRAINT reversals_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: settlement_allocations settlement_allocations_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlement_allocations
    ADD CONSTRAINT settlement_allocations_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE RESTRICT;


--
-- Name: settlement_allocations settlement_allocations_settlement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlement_allocations
    ADD CONSTRAINT settlement_allocations_settlement_id_foreign FOREIGN KEY (settlement_id) REFERENCES public.settlements(id) ON DELETE CASCADE;


--
-- Name: settlements settlements_cancelled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_cancelled_by_foreign FOREIGN KEY (cancelled_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: settlements settlements_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: settlements settlements_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE RESTRICT;


--
-- Name: settlements settlements_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: settlements settlements_voided_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.settlements
    ADD CONSTRAINT settlements_voided_by_foreign FOREIGN KEY (voided_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: student_categories student_categories_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_categories
    ADD CONSTRAINT student_categories_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: student_enrollments student_enrollments_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_academic_year_id_foreign FOREIGN KEY (academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: student_enrollments student_enrollments_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_class_id_foreign FOREIGN KEY (class_id) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: student_enrollments student_enrollments_previous_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_previous_enrollment_id_foreign FOREIGN KEY (previous_enrollment_id) REFERENCES public.student_enrollments(id) ON DELETE SET NULL;


--
-- Name: student_enrollments student_enrollments_promotion_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_promotion_batch_id_foreign FOREIGN KEY (promotion_batch_id) REFERENCES public.promotion_batches(id) ON DELETE SET NULL;


--
-- Name: student_enrollments student_enrollments_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE RESTRICT;


--
-- Name: student_enrollments student_enrollments_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_enrollments
    ADD CONSTRAINT student_enrollments_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: student_fee_mappings student_fee_mappings_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: student_fee_mappings student_fee_mappings_fee_matrix_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_fee_matrix_id_foreign FOREIGN KEY (fee_matrix_id) REFERENCES public.fee_matrix(id) ON DELETE RESTRICT;


--
-- Name: student_fee_mappings student_fee_mappings_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id) ON DELETE CASCADE;


--
-- Name: student_fee_mappings student_fee_mappings_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: student_fee_mappings student_fee_mappings_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_fee_mappings
    ADD CONSTRAINT student_fee_mappings_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: student_obligations student_obligations_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_academic_year_id_foreign FOREIGN KEY (academic_year_id) REFERENCES public.academic_years(id) ON DELETE RESTRICT;


--
-- Name: student_obligations student_obligations_class_id_snapshot_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_class_id_snapshot_foreign FOREIGN KEY (class_id_snapshot) REFERENCES public.classes(id) ON DELETE RESTRICT;


--
-- Name: student_obligations student_obligations_fee_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_fee_type_id_foreign FOREIGN KEY (fee_type_id) REFERENCES public.fee_types(id);


--
-- Name: student_obligations student_obligations_student_enrollment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_student_enrollment_id_foreign FOREIGN KEY (student_enrollment_id) REFERENCES public.student_enrollments(id) ON DELETE RESTRICT;


--
-- Name: student_obligations student_obligations_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id);


--
-- Name: student_obligations student_obligations_transaction_item_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_transaction_item_id_foreign FOREIGN KEY (transaction_item_id) REFERENCES public.transaction_items(id);


--
-- Name: student_obligations student_obligations_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.student_obligations
    ADD CONSTRAINT student_obligations_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: students students_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.student_categories(id);


--
-- Name: students students_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_class_id_foreign FOREIGN KEY (class_id) REFERENCES public.classes(id);


--
-- Name: students students_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.students
    ADD CONSTRAINT students_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: transaction_items transaction_items_fee_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_fee_type_id_foreign FOREIGN KEY (fee_type_id) REFERENCES public.fee_types(id);


--
-- Name: transaction_items transaction_items_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_cancelled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_cancelled_by_foreign FOREIGN KEY (cancelled_by) REFERENCES public.users(id);


--
-- Name: transactions transactions_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: transactions transactions_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.students(id);


--
-- Name: transactions transactions_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: users users_unit_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES public.units(id) ON DELETE RESTRICT;


--
-- Name: mv_ar_outstanding; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: sakumi_user
--

REFRESH MATERIALIZED VIEW public.mv_ar_outstanding;


--
-- Name: mv_daily_cash_summary; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: sakumi_user
--

REFRESH MATERIALIZED VIEW public.mv_daily_cash_summary;


--
-- PostgreSQL database dump complete
--

\unrestrict gbOAZVt3ekZC6GXoXVoNMiQDQvHKgFyLcEAWi9Vp7u18tFjirIc5Yrg8sxO24Z4

