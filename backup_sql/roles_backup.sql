--
-- PostgreSQL database dump
--

\restrict PP92htukMRNbZLA1xFWLiN8gkreUiPmq15uN4PkgGadSB0JLU6qSvopfOxrl5GU

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
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


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
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sakumi_user
--

SELECT pg_catalog.setval('public.roles_id_seq', 11, true);


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
-- PostgreSQL database dump complete
--

\unrestrict PP92htukMRNbZLA1xFWLiN8gkreUiPmq15uN4PkgGadSB0JLU6qSvopfOxrl5GU

