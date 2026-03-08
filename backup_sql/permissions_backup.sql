--
-- PostgreSQL database dump
--

\restrict QFfo11b7bh1VHodqyWjOx0E6ioAZHiw5VNH6fNpkgoJ5QIycYyS9MkaqEH06guz

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

SET default_tablespace = '';

SET default_table_access_method = heap;

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
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


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
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.permissions_id_seq', 85, true);


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
-- PostgreSQL database dump complete
--

\unrestrict QFfo11b7bh1VHodqyWjOx0E6ioAZHiw5VNH6fNpkgoJ5QIycYyS9MkaqEH06guz

