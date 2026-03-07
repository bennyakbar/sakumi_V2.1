--
-- PostgreSQL database dump
--

\restrict 4j6nD0rA9euPYhhUuoaIAVwALStbYBJdj5s2roL1jBBPDmmAhOkoQKAEnlFkT8o

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
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: sakumi_user
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO sakumi_user;

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
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: sakumi_user
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sakumi_user
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 4j6nD0rA9euPYhhUuoaIAVwALStbYBJdj5s2roL1jBBPDmmAhOkoQKAEnlFkT8o

