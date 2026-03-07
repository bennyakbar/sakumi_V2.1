CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "is_active" tinyint(1) not null default '1'
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "activity_log"(
  "id" integer primary key autoincrement not null,
  "log_name" varchar,
  "description" text not null,
  "subject_type" varchar,
  "subject_id" integer,
  "causer_type" varchar,
  "causer_id" integer,
  "properties" text,
  "created_at" datetime,
  "updated_at" datetime,
  "event" varchar,
  "batch_uuid" varchar
);
CREATE INDEX "subject" on "activity_log"("subject_type", "subject_id");
CREATE INDEX "causer" on "activity_log"("causer_type", "causer_id");
CREATE INDEX "activity_log_log_name_index" on "activity_log"("log_name");
CREATE TABLE IF NOT EXISTS "classes"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "level" integer not null,
  "academic_year" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "classes_name_academic_year_unique" on "classes"(
  "name",
  "academic_year"
);
CREATE TABLE IF NOT EXISTS "student_categories"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "description" text,
  "discount_percentage" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "student_categories_code_unique" on "student_categories"(
  "code"
);
CREATE TABLE IF NOT EXISTS "students"(
  "id" integer primary key autoincrement not null,
  "nis" varchar not null,
  "nisn" varchar,
  "name" varchar not null,
  "class_id" integer not null,
  "category_id" integer not null,
  "gender" varchar,
  "birth_date" date,
  "birth_place" varchar,
  "parent_name" varchar,
  "parent_phone" varchar,
  "parent_whatsapp" varchar,
  "address" text,
  "status" varchar not null default 'active',
  "enrollment_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("class_id") references "classes"("id"),
  foreign key("category_id") references "student_categories"("id")
);
CREATE INDEX "students_status_index" on "students"("status");
CREATE INDEX "students_class_id_index" on "students"("class_id");
CREATE UNIQUE INDEX "students_nis_unique" on "students"("nis");
CREATE UNIQUE INDEX "students_nisn_unique" on "students"("nisn");
CREATE TABLE IF NOT EXISTS "fee_types"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "description" text,
  "is_monthly" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "fee_types_code_unique" on "fee_types"("code");
CREATE TABLE IF NOT EXISTS "fee_matrix"(
  "id" integer primary key autoincrement not null,
  "fee_type_id" integer not null,
  "class_id" integer,
  "category_id" integer,
  "amount" numeric not null,
  "effective_from" date not null,
  "effective_to" date,
  "is_active" tinyint(1) not null default '1',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("fee_type_id") references "fee_types"("id"),
  foreign key("class_id") references "classes"("id"),
  foreign key("category_id") references "student_categories"("id")
);
CREATE INDEX "idx_feematrix_lookup" on "fee_matrix"(
  "fee_type_id",
  "class_id",
  "category_id",
  "effective_from"
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "transaction_number" varchar not null,
  "transaction_date" date not null,
  "type" varchar not null,
  "student_id" integer,
  "payment_method" varchar,
  "total_amount" numeric not null,
  "description" text,
  "receipt_path" varchar,
  "proof_path" varchar,
  "status" varchar not null default 'completed',
  "cancelled_at" datetime,
  "cancelled_by" integer,
  "cancellation_reason" text,
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id"),
  foreign key("cancelled_by") references "users"("id"),
  foreign key("created_by") references "users"("id")
);
CREATE INDEX "transactions_transaction_date_index" on "transactions"(
  "transaction_date"
);
CREATE INDEX "transactions_student_id_index" on "transactions"("student_id");
CREATE INDEX "transactions_type_status_index" on "transactions"(
  "type",
  "status"
);
CREATE UNIQUE INDEX "transactions_transaction_number_unique" on "transactions"(
  "transaction_number"
);
CREATE TABLE IF NOT EXISTS "transaction_items"(
  "id" integer primary key autoincrement not null,
  "transaction_id" integer not null,
  "fee_type_id" integer not null,
  "description" varchar,
  "amount" numeric not null,
  "month" integer,
  "year" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("transaction_id") references "transactions"("id") on delete cascade,
  foreign key("fee_type_id") references "fee_types"("id")
);
CREATE INDEX "transaction_items_transaction_id_index" on "transaction_items"(
  "transaction_id"
);
CREATE TABLE IF NOT EXISTS "student_obligations"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "fee_type_id" integer not null,
  "month" integer not null,
  "year" integer not null,
  "amount" numeric not null,
  "is_paid" tinyint(1) not null default '0',
  "paid_amount" numeric not null default '0',
  "paid_at" datetime,
  "transaction_item_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id"),
  foreign key("fee_type_id") references "fee_types"("id"),
  foreign key("transaction_item_id") references "transaction_items"("id")
);
CREATE UNIQUE INDEX "uq_obligation_period" on "student_obligations"(
  "student_id",
  "fee_type_id",
  "month",
  "year"
);
CREATE INDEX "student_obligations_year_month_index" on "student_obligations"(
  "year",
  "month"
);
CREATE INDEX idx_obligations_unpaid ON student_obligations(
  student_id,
  is_paid
) WHERE is_paid = false;
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "type" varchar not null,
  "message" text not null,
  "recipient_phone" varchar,
  "whatsapp_status" varchar not null default 'pending',
  "whatsapp_sent_at" datetime,
  "whatsapp_response" text,
  "is_read" tinyint(1) not null default '0',
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("student_id") references "students"("id")
);
CREATE INDEX "notifications_whatsapp_status_index" on "notifications"(
  "whatsapp_status"
);
CREATE INDEX "notifications_student_id_index" on "notifications"("student_id");
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "group" varchar not null default 'system',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "settings_key_unique" on "settings"("key");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_02_11_102213_create_permission_tables',1);
INSERT INTO migrations VALUES(5,'2026_02_11_102215_create_activity_log_table',1);
INSERT INTO migrations VALUES(6,'2026_02_11_102216_add_event_column_to_activity_log_table',1);
INSERT INTO migrations VALUES(7,'2026_02_11_102217_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO migrations VALUES(8,'2026_02_11_110001_create_classes_table',1);
INSERT INTO migrations VALUES(9,'2026_02_11_110002_create_student_categories_table',1);
INSERT INTO migrations VALUES(10,'2026_02_11_110003_create_students_table',1);
INSERT INTO migrations VALUES(11,'2026_02_11_110004_create_fee_types_table',1);
INSERT INTO migrations VALUES(12,'2026_02_11_110005_create_fee_matrix_table',1);
INSERT INTO migrations VALUES(13,'2026_02_11_110006_create_transactions_table',1);
INSERT INTO migrations VALUES(14,'2026_02_11_110007_create_transaction_items_table',1);
INSERT INTO migrations VALUES(15,'2026_02_11_110008_create_student_obligations_table',1);
INSERT INTO migrations VALUES(16,'2026_02_11_110009_create_notifications_table',1);
INSERT INTO migrations VALUES(17,'2026_02_11_110010_create_settings_table',1);
INSERT INTO migrations VALUES(18,'2026_02_11_110011_add_is_active_to_users_table',1);
