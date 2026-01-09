--
-- PostgreSQL database dump
--

\restrict vWcMdAIsBFFb2HmnRyri135TrknquDqC8ifG2nYm8JdPvyha76xEIppJOhXl0w7

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
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
-- Name: apoderados; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.apoderados (
    id bigint NOT NULL,
    tipo_documento text NOT NULL,
    nro_documento character varying(15) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellido_paterno character varying(50) NOT NULL,
    apellido_materno character varying(50) NOT NULL,
    telefono character varying(20) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: apoderados_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.apoderados_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: apoderados_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.apoderados_id_seq OWNED BY public.apoderados.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cronogramas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cronogramas (
    id bigint NOT NULL,
    matricula_id bigint NOT NULL,
    num_cuotas integer NOT NULL,
    monto_total numeric(10,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cronogramas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cronogramas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cronogramas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cronogramas_id_seq OWNED BY public.cronogramas.id;


--
-- Name: cursos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cursos (
    id_curso bigint NOT NULL,
    nombre_curso character varying(150) NOT NULL,
    duracion integer,
    fecha_inicio date,
    fecha_termino date,
    id_programa bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cursos_id_curso_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cursos_id_curso_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cursos_id_curso_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cursos_id_curso_seq OWNED BY public.cursos.id_curso;


--
-- Name: docentes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.docentes (
    id bigint NOT NULL,
    tipo_documento character varying(255) NOT NULL,
    nro_documento character varying(255) NOT NULL,
    nombres character varying(255) NOT NULL,
    apellido_paterno character varying(255) NOT NULL,
    apellido_materno character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: docentes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.docentes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: docentes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.docentes_id_seq OWNED BY public.docentes.id;


--
-- Name: empleados; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.empleados (
    id bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    apellido_paterno character varying(255) NOT NULL,
    apellido_materno character varying(255),
    correo character varying(255) NOT NULL,
    celular character varying(20),
    tipo_documento character varying(50) NOT NULL,
    num_documento character varying(30) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: empleados_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.empleados_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: empleados_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.empleados_id_seq OWNED BY public.empleados.id;


--
-- Name: especialidades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.especialidades (
    id_especialidad bigint CONSTRAINT rubros_id_rubro_not_null NOT NULL,
    nombre_especialidad character varying(150) CONSTRAINT rubros_nombre_rubro_not_null NOT NULL,
    costo_mensual numeric(10,2) CONSTRAINT rubros_costo_mensual_not_null NOT NULL,
    num_resolucion character varying(100),
    fecha_registro date,
    fecha_inicio_vigencia date,
    fecha_fin_vigencia date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: estudiantes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estudiantes (
    id bigint NOT NULL,
    tipo_documento text NOT NULL,
    nro_documento character varying(15) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellido_paterno character varying(50) NOT NULL,
    apellido_materno character varying(50) NOT NULL,
    genero character varying(20),
    estado_civil character varying(20),
    fecha_nacimiento date,
    telefono character varying(20),
    direccion character varying(200),
    email character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    apoderado_id bigint,
    grado_instruccion character varying(255),
    provincia character varying(255) DEFAULT 'Lima'::character varying NOT NULL,
    distrito character varying(255),
    codigo_contribuyente character varying(20),
    CONSTRAINT estudiantes_distrito_check CHECK (((distrito)::text = ANY ((ARRAY['Ancón'::character varying, 'Ate'::character varying, 'Barranco'::character varying, 'Breña'::character varying, 'Carabayllo'::character varying, 'Chaclacayo'::character varying, 'Chorrillos'::character varying, 'Cieneguilla'::character varying, 'Comas'::character varying, 'El Agustino'::character varying, 'Independencia'::character varying, 'Jesús María'::character varying, 'La Molina'::character varying, 'La Victoria'::character varying, 'Lima'::character varying, 'Lince'::character varying, 'Los Olivos'::character varying, 'Lurigancho'::character varying, 'Lurín'::character varying, 'Magdalena del Mar'::character varying, 'Miraflores'::character varying, 'Pachacámac'::character varying, 'Pucusana'::character varying, 'Pueblo Libre'::character varying, 'Puente Piedra'::character varying, 'Punta Hermosa'::character varying, 'Punta Negra'::character varying, 'Rímac'::character varying, 'San Bartolo'::character varying, 'San Borja'::character varying, 'San Isidro'::character varying, 'San Juan de Lurigancho'::character varying, 'San Juan de Miraflores'::character varying, 'San Luis'::character varying, 'San Martín de Porres'::character varying, 'San Miguel'::character varying, 'Santa Anita'::character varying, 'Santa María del Mar'::character varying, 'Santa Rosa'::character varying, 'Santiago de Surco'::character varying, 'Surquillo'::character varying, 'Villa El Salvador'::character varying, 'Villa María del Triunfo'::character varying])::text[]))),
    CONSTRAINT estudiantes_grado_instruccion_check CHECK (((grado_instruccion)::text = ANY ((ARRAY['Sin estudios'::character varying, 'Primaria incompleta'::character varying, 'Primaria completa'::character varying, 'Secundaria incompleta'::character varying, 'Secundaria completa'::character varying, 'Superior técnica'::character varying, 'Superior universitaria'::character varying, 'Posgrado'::character varying])::text[]))),
    CONSTRAINT estudiantes_provincia_check CHECK (((provincia)::text = 'Lima'::text))
);


--
-- Name: COLUMN estudiantes.codigo_contribuyente; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.estudiantes.codigo_contribuyente IS 'Código de contribuyente en Oracle (formato X0000001)';


--
-- Name: estudiantes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.estudiantes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: estudiantes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.estudiantes_id_seq OWNED BY public.estudiantes.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: horarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.horarios (
    id_horario bigint CONSTRAINT oferta_academica_id_oferta_not_null NOT NULL,
    id_programa bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    turno character varying(255),
    dias character varying(255),
    id_docente bigint,
    modalidad character varying(255),
    aula character varying(255),
    hora_inicio time(0) without time zone,
    hora_fin time(0) without time zone,
    vacantes integer DEFAULT 20 NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT oferta_academica_modalidad_check CHECK (((modalidad)::text = ANY ((ARRAY['Presencial'::character varying, 'Virtual'::character varying, 'Semipresencial'::character varying])::text[]))),
    CONSTRAINT oferta_academica_turno_check CHECK (((turno)::text = ANY ((ARRAY['Mañana'::character varying, 'Tarde'::character varying])::text[])))
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: matriculas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.matriculas (
    id bigint NOT NULL,
    codigo_inscripcion character varying(255) CONSTRAINT matriculas_codigo_not_null NOT NULL,
    estudiante_id bigint NOT NULL,
    estado character varying(255) DEFAULT 'activa'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    horario_id bigint,
    tipo_matricula character varying(255) DEFAULT 'Programa'::character varying NOT NULL,
    id_curso bigint,
    motivo_anulacion text,
    fecha_anulacion timestamp(0) without time zone,
    documento_path character varying(255),
    tipo_certificado character varying(255),
    CONSTRAINT matriculas_tipo_matricula_check CHECK (((tipo_matricula)::text = ANY ((ARRAY['Programa'::character varying, 'Formación continua'::character varying, 'Curso'::character varying, 'Módulo'::character varying])::text[])))
);


--
-- Name: matriculas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.matriculas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: matriculas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.matriculas_id_seq OWNED BY public.matriculas.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notas (
    id bigint NOT NULL,
    matricula_id bigint NOT NULL,
    curso_id bigint NOT NULL,
    docente_id bigint,
    nota_numerica numeric(4,2),
    nota_letra character varying(5),
    pdf_calificacion character varying(255),
    observaciones text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: notas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notas_id_seq OWNED BY public.notas.id;


--
-- Name: oferta_academica_id_oferta_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oferta_academica_id_oferta_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oferta_academica_id_oferta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oferta_academica_id_oferta_seq OWNED BY public.horarios.id_horario;


--
-- Name: pagos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pagos (
    id bigint NOT NULL,
    cronograma_id bigint NOT NULL,
    nro_cuota integer NOT NULL,
    monto numeric(10,2) NOT NULL,
    estado character varying(255) DEFAULT 'Pendiente'::character varying,
    fecha_vencimiento date NOT NULL,
    fecha_pago date,
    metodo_pago character varying(50),
    evidencia_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    num_liquidacion character varying(255),
    fecha_liquidacion date,
    usuario_id bigint
);


--
-- Name: pagos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pagos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pagos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pagos_id_seq OWNED BY public.pagos.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: permisos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permisos (
    id bigint NOT NULL,
    recurso character varying(255) NOT NULL,
    nombre character varying(255) NOT NULL,
    grupo character varying(255),
    descripcion text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permisos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permisos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permisos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permisos_id_seq OWNED BY public.permisos.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: programas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.programas (
    id_programa bigint NOT NULL,
    nombre_programa character varying(150) NOT NULL,
    duracion integer,
    num_cursos integer,
    id_especialidad bigint CONSTRAINT programas_id_rubro_not_null NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tipo_programa character varying(255) DEFAULT 'Programa'::character varying NOT NULL,
    CONSTRAINT programas_tipo_programa_check CHECK (((tipo_programa)::text = ANY ((ARRAY['Programa'::character varying, 'Formación continua'::character varying])::text[])))
);


--
-- Name: programas_id_programa_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.programas_id_programa_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: programas_id_programa_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.programas_id_programa_seq OWNED BY public.programas.id_programa;


--
-- Name: role_permiso; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_permiso (
    id bigint NOT NULL,
    role_id bigint NOT NULL,
    permiso_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: role_permiso_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_permiso_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_permiso_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_permiso_id_seq OWNED BY public.role_permiso.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    nombre character varying(255) NOT NULL,
    descripcion text,
    es_admin boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: rubros_id_rubro_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rubros_id_rubro_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rubros_id_rubro_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rubros_id_rubro_seq OWNED BY public.especialidades.id_especialidad;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id bigint NOT NULL,
    empleado_id bigint,
    usuario character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role_id bigint,
    activo boolean DEFAULT true NOT NULL,
    docente_id bigint,
    estudiante_id bigint
);


--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuarios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: apoderados id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.apoderados ALTER COLUMN id SET DEFAULT nextval('public.apoderados_id_seq'::regclass);


--
-- Name: cronogramas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cronogramas ALTER COLUMN id SET DEFAULT nextval('public.cronogramas_id_seq'::regclass);


--
-- Name: cursos id_curso; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cursos ALTER COLUMN id_curso SET DEFAULT nextval('public.cursos_id_curso_seq'::regclass);


--
-- Name: docentes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docentes ALTER COLUMN id SET DEFAULT nextval('public.docentes_id_seq'::regclass);


--
-- Name: empleados id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empleados ALTER COLUMN id SET DEFAULT nextval('public.empleados_id_seq'::regclass);


--
-- Name: especialidades id_especialidad; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.especialidades ALTER COLUMN id_especialidad SET DEFAULT nextval('public.rubros_id_rubro_seq'::regclass);


--
-- Name: estudiantes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes ALTER COLUMN id SET DEFAULT nextval('public.estudiantes_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: horarios id_horario; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horarios ALTER COLUMN id_horario SET DEFAULT nextval('public.oferta_academica_id_oferta_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: matriculas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas ALTER COLUMN id SET DEFAULT nextval('public.matriculas_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas ALTER COLUMN id SET DEFAULT nextval('public.notas_id_seq'::regclass);


--
-- Name: pagos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id SET DEFAULT nextval('public.pagos_id_seq'::regclass);


--
-- Name: permisos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos ALTER COLUMN id SET DEFAULT nextval('public.permisos_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: programas id_programa; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programas ALTER COLUMN id_programa SET DEFAULT nextval('public.programas_id_programa_seq'::regclass);


--
-- Name: role_permiso id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permiso ALTER COLUMN id SET DEFAULT nextval('public.role_permiso_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Name: apoderados apoderados_nro_documento_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.apoderados
    ADD CONSTRAINT apoderados_nro_documento_unique UNIQUE (nro_documento);


--
-- Name: apoderados apoderados_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.apoderados
    ADD CONSTRAINT apoderados_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: pagos cronograma_cuota_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT cronograma_cuota_unique UNIQUE (cronograma_id, nro_cuota);


--
-- Name: cronogramas cronogramas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cronogramas
    ADD CONSTRAINT cronogramas_pkey PRIMARY KEY (id);


--
-- Name: cursos cursos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cursos
    ADD CONSTRAINT cursos_pkey PRIMARY KEY (id_curso);


--
-- Name: docentes docentes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.docentes
    ADD CONSTRAINT docentes_pkey PRIMARY KEY (id);


--
-- Name: empleados empleados_correo_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empleados
    ADD CONSTRAINT empleados_correo_unique UNIQUE (correo);


--
-- Name: empleados empleados_documento_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empleados
    ADD CONSTRAINT empleados_documento_unique UNIQUE (tipo_documento, num_documento);


--
-- Name: empleados empleados_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.empleados
    ADD CONSTRAINT empleados_pkey PRIMARY KEY (id);


--
-- Name: estudiantes estudiantes_direccion_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes
    ADD CONSTRAINT estudiantes_direccion_unique UNIQUE (direccion);


--
-- Name: estudiantes estudiantes_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes
    ADD CONSTRAINT estudiantes_email_unique UNIQUE (email);


--
-- Name: estudiantes estudiantes_nro_documento_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes
    ADD CONSTRAINT estudiantes_nro_documento_unique UNIQUE (nro_documento);


--
-- Name: estudiantes estudiantes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes
    ADD CONSTRAINT estudiantes_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: matriculas matriculas_codigo_inscripcion_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_codigo_inscripcion_unique UNIQUE (codigo_inscripcion);


--
-- Name: matriculas matriculas_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_codigo_unique UNIQUE (codigo_inscripcion);


--
-- Name: matriculas matriculas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notas notas_matricula_curso_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas
    ADD CONSTRAINT notas_matricula_curso_unique UNIQUE (matricula_id, curso_id);


--
-- Name: notas notas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas
    ADD CONSTRAINT notas_pkey PRIMARY KEY (id);


--
-- Name: horarios oferta_academica_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horarios
    ADD CONSTRAINT oferta_academica_pkey PRIMARY KEY (id_horario);


--
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permisos permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_pkey PRIMARY KEY (id);


--
-- Name: permisos permisos_recurso_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permisos_recurso_unique UNIQUE (recurso);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: programas programas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programas
    ADD CONSTRAINT programas_pkey PRIMARY KEY (id_programa);


--
-- Name: role_permiso role_permiso_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permiso
    ADD CONSTRAINT role_permiso_pkey PRIMARY KEY (id);


--
-- Name: role_permiso role_permiso_role_id_permiso_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permiso
    ADD CONSTRAINT role_permiso_role_id_permiso_id_unique UNIQUE (role_id, permiso_id);


--
-- Name: roles roles_nombre_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_unique UNIQUE (nombre);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: especialidades rubros_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT rubros_pkey PRIMARY KEY (id_especialidad);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_docente_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_docente_id_unique UNIQUE (docente_id);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: usuarios usuarios_usuario_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_unique UNIQUE (usuario);


--
-- Name: idx_matriculas_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_matriculas_created_at ON public.matriculas USING btree (created_at);


--
-- Name: idx_matriculas_estado_created; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_matriculas_estado_created ON public.matriculas USING btree (estado, created_at);


--
-- Name: idx_pagos_estado_pago; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pagos_estado_pago ON public.pagos USING btree (estado, fecha_pago);


--
-- Name: idx_pagos_estado_vencimiento; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pagos_estado_vencimiento ON public.pagos USING btree (estado, fecha_vencimiento);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notas_curso_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notas_curso_id_index ON public.notas USING btree (curso_id);


--
-- Name: notas_docente_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notas_docente_id_index ON public.notas USING btree (docente_id);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: cronogramas cronogramas_matricula_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cronogramas
    ADD CONSTRAINT cronogramas_matricula_id_foreign FOREIGN KEY (matricula_id) REFERENCES public.matriculas(id) ON DELETE CASCADE;


--
-- Name: cursos cursos_id_programa_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cursos
    ADD CONSTRAINT cursos_id_programa_foreign FOREIGN KEY (id_programa) REFERENCES public.programas(id_programa) ON DELETE SET NULL;


--
-- Name: estudiantes estudiantes_apoderado_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estudiantes
    ADD CONSTRAINT estudiantes_apoderado_id_foreign FOREIGN KEY (apoderado_id) REFERENCES public.apoderados(id) ON DELETE SET NULL;


--
-- Name: matriculas matriculas_estudiante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_estudiante_id_foreign FOREIGN KEY (estudiante_id) REFERENCES public.estudiantes(id);


--
-- Name: matriculas matriculas_horario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_horario_id_foreign FOREIGN KEY (horario_id) REFERENCES public.horarios(id_horario) ON DELETE CASCADE;


--
-- Name: matriculas matriculas_id_curso_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.matriculas
    ADD CONSTRAINT matriculas_id_curso_foreign FOREIGN KEY (id_curso) REFERENCES public.cursos(id_curso) ON DELETE SET NULL;


--
-- Name: notas notas_curso_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas
    ADD CONSTRAINT notas_curso_id_foreign FOREIGN KEY (curso_id) REFERENCES public.cursos(id_curso) ON DELETE CASCADE;


--
-- Name: notas notas_docente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas
    ADD CONSTRAINT notas_docente_id_foreign FOREIGN KEY (docente_id) REFERENCES public.docentes(id) ON DELETE SET NULL;


--
-- Name: notas notas_matricula_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notas
    ADD CONSTRAINT notas_matricula_id_foreign FOREIGN KEY (matricula_id) REFERENCES public.matriculas(id) ON DELETE CASCADE;


--
-- Name: horarios oferta_academica_docente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horarios
    ADD CONSTRAINT oferta_academica_docente_id_foreign FOREIGN KEY (id_docente) REFERENCES public.docentes(id) ON DELETE SET NULL;


--
-- Name: horarios oferta_academica_id_programa_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horarios
    ADD CONSTRAINT oferta_academica_id_programa_foreign FOREIGN KEY (id_programa) REFERENCES public.programas(id_programa) ON DELETE SET NULL;


--
-- Name: pagos pagos_cronograma_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_cronograma_id_foreign FOREIGN KEY (cronograma_id) REFERENCES public.cronogramas(id) ON DELETE CASCADE;


--
-- Name: pagos pagos_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE SET NULL;


--
-- Name: programas programas_id_rubro_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programas
    ADD CONSTRAINT programas_id_rubro_foreign FOREIGN KEY (id_especialidad) REFERENCES public.especialidades(id_especialidad) ON DELETE CASCADE;


--
-- Name: role_permiso role_permiso_permiso_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permiso
    ADD CONSTRAINT role_permiso_permiso_id_foreign FOREIGN KEY (permiso_id) REFERENCES public.permisos(id) ON DELETE CASCADE;


--
-- Name: role_permiso role_permiso_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_permiso
    ADD CONSTRAINT role_permiso_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: usuarios usuarios_docente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_docente_id_foreign FOREIGN KEY (docente_id) REFERENCES public.docentes(id) ON DELETE SET NULL;


--
-- Name: usuarios usuarios_empleado_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_empleado_id_foreign FOREIGN KEY (empleado_id) REFERENCES public.empleados(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: usuarios usuarios_estudiante_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_estudiante_id_foreign FOREIGN KEY (estudiante_id) REFERENCES public.estudiantes(id) ON DELETE SET NULL;


--
-- Name: usuarios usuarios_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict vWcMdAIsBFFb2HmnRyri135TrknquDqC8ifG2nYm8JdPvyha76xEIppJOhXl0w7

--
-- PostgreSQL database dump
--

\restrict 4IsRq9cfSkQJ1wqgbIjiMKMQiOmsYuX7W110rnsCGwCJMIOGMah1M0qAeKbDk26

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_10_28_151941_create_estudiantes_table	1
5	2025_10_28_201053_create_docentes_table	1
6	2025_10_28_202858_create_seccions_table	1
7	2025_10_29_143524_create_modulos_table	1
8	2025_10_29_143754_create_docente_modulo_table	1
9	2025_10_29_145415_change_modulo_enum_to_foreign_id_table	1
10	2025_10_29_152251_change_modulo_enum_to_foreign_id_table	1
11	2025_10_29_193134_create_apoderados_table	1
12	2025_10_29_194520_add_apoderado_id_to_estudiante_table	1
13	2025_10_31_040416_delete_apoderado_attr_email	1
14	2025_10_31_153936_alter_estudiantes_make_extra_info_nullable	1
15	2025_10_31_174517_add_codigo_unico_secciones_table	1
16	2025_10_31_202001_change_length_tipo_documento	1
17	2025_10_31_202100_change_length_tipo_documento	1
18	2025_11_03_014001_create_matriculas_table	1
19	2025_11_07_205238_create_empleados_table	1
20	2025_11_07_205422_create_usuarios_table	1
21	2025_11_13_170640_create_rubros_table	1
22	2025_11_13_170646_create_programas_table	1
23	2025_11_13_170650_create_cursos_table	1
24	2025_11_13_170655_create_oferta_academica_table	1
25	2025_11_13_175844_add_oferta_academica_id_to_matriculas_table	1
26	2025_11_13_194418_drop_modulos_and_relations	1
27	2025_11_13_195239_drop_seccions_and_relations	1
28	2025_11_14_142734_move_campos_programas_to_oferta_academica	1
29	2025_11_14_144018_move_modalidad_from_programas_to_oferta_academica	1
30	2025_11_14_193032_rename_oferta_academica_to_secciones_table	1
31	2025_11_14_195951_rename_oferta_columns_to_seccion_columns	1
32	2025_11_14_205359_remove_id_rubro_from_seccion_table	1
33	2025_11_17_172355_update_programas_table_for_new_design	1
34	2025_11_17_172432_update_seccion_table_for_new_design	1
35	2025_11_17_172445_update_matriculas_table_for_new_design	1
36	2025_11_17_172548_update_cursos_table_for_new_design	1
37	2025_11_17_202833_rename_rubros_to_especialidades	1
38	2025_11_17_210328_rename_id_rubro_to_id_especialidad_in_programas	1
39	2025_11_18_162630_add_grado_instruccion_provincia_distrito_to_estudiantes_table	1
40	2025_11_19_154606_create_cronogramas_table	1
41	2025_11_19_154626_create_pagos_table	1
42	2025_11_20_191958_add_liquidacion_fields_to_pagos_table	1
43	2025_11_27_155719_rename_seccion_to_horarios	1
44	2025_11_27_180302_actualizar_valores_enums_tipo_matricula_y_tipo_programa	1
45	2025_11_28_170122_create_permisos_table	1
46	2025_11_28_170122_create_roles_table	1
47	2025_11_28_170123_create_role_permiso_table	1
48	2025_11_28_170125_add_role_id_to_usuarios_table	1
49	2025_12_04_122500_drop_rol_from_usuarios_table	1
50	2025_12_05_143431_add_activo_to_usuarios_table	1
51	2025_12_05_193300_fix_horarios_table_structure	1
52	2025_12_05_201512_add_vacantes_and_activo_to_horarios_table	1
53	2025_12_05_205155_add_anulacion_fields_to_matriculas_table	1
54	2025_12_10_154120_add_usuario_id_to_pagos_table	1
55	2025_12_12_202948_add_dashboard_performance_indexes	1
56	2025_12_18_161428_create_personal_access_tokens_table	1
57	2025_12_30_095414_create_notas_table	1
58	2025_12_30_095429_add_docente_id_to_usuarios_table	1
59	2026_01_05_100000_remove_codigo_from_pagos_table	1
60	2026_01_05_102300_drop_notas_table	1
61	2026_01_05_120800_create_notas_table	1
62	2026_01_06_121434_add_codigo_contribuyente_to_estudiantes_table	2
63	2026_01_06_120000_add_documento_fields_to_matriculas_table	3
64	2026_01_07_143958_change_estado_to_string_in_pagos_table	3
65	2026_01_08_145152_add_estudiante_id_to_usuarios_table	4
66	2026_01_08_150411_make_empleado_id_nullable_in_usuarios_table	5
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 66, true);


--
-- PostgreSQL database dump complete
--

\unrestrict 4IsRq9cfSkQJ1wqgbIjiMKMQiOmsYuX7W110rnsCGwCJMIOGMah1M0qAeKbDk26

