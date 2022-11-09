--
-- PostgreSQL database dump
--

-- Dumped from database version 10.4 (Debian 10.4-2.pgdg90+1)
-- Dumped by pg_dump version 10.4 (Debian 10.4-2.pgdg90+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: tiger; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA tiger;


--
-- Name: tiger_data; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA tiger_data;


--
-- Name: topology; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA topology;


--
-- Name: SCHEMA topology; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA topology IS 'PostGIS Topology schema';


--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: fuzzystrmatch; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA public;


--
-- Name: EXTENSION fuzzystrmatch; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION fuzzystrmatch IS 'determine similarities and distance between strings';


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry, geography, and raster spatial types and functions';


--
-- Name: postgis_tiger_geocoder; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder WITH SCHEMA tiger;


--
-- Name: EXTENSION postgis_tiger_geocoder; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_tiger_geocoder IS 'PostGIS tiger geocoder and reverse geocoder';


--
-- Name: postgis_topology; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_topology WITH SCHEMA topology;


--
-- Name: EXTENSION postgis_topology; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_topology IS 'PostGIS topology spatial types and functions';


--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


--
-- Name: frequency; Type: DOMAIN; Schema: public; Owner: -
--

CREATE DOMAIN public.frequency AS character varying
	CONSTRAINT frequency_check CHECK (((VALUE)::text = ANY (ARRAY[('once'::character varying)::text, ('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('yearly'::character varying)::text])));


--
-- Name: days_in_month(date); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.days_in_month(check_date date) RETURNS integer
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
  first_of_month DATE := check_date - ((extract(day from check_date) - 1)||' days')::interval;
BEGIN
  RETURN extract(day from first_of_month + '1 month'::interval - first_of_month);
END;
$$;


--
-- Name: generate_recurrences(interval, date, date, date, date, integer, integer, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.generate_recurrences(duration interval, original_start_date date, original_end_date date, range_start date, range_end date, repeat_month integer, repeat_week integer, repeat_day integer) RETURNS SETOF date
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
  start_date DATE := original_start_date;
  next_date DATE;
  intervals INT := FLOOR(intervals_between(original_start_date, range_start, duration));
  current_month INT;
  current_week INT;
BEGIN
  IF repeat_month IS NOT NULL THEN
    start_date := start_date + (((12 + repeat_month - cast(extract(month from start_date) as int)) % 12) || ' months')::interval;
  END IF;
  IF repeat_week IS NULL AND repeat_day IS NOT NULL THEN
    IF duration = '7 days'::interval THEN
      start_date := start_date + (((7 + repeat_day - cast(extract(dow from start_date) as int)) % 7) || ' days')::interval;
    ELSE
      start_date := start_date + (repeat_day - extract(day from start_date) || ' days')::interval;
    END IF;
  END IF;
  LOOP
    next_date := start_date + duration * intervals;
    IF repeat_week IS NOT NULL AND repeat_day IS NOT NULL THEN
      current_month := extract(month from next_date);
      next_date := next_date + (((7 + repeat_day - cast(extract(dow from next_date) as int)) % 7) || ' days')::interval;
      IF extract(month from next_date) != current_month THEN
        next_date := next_date - '7 days'::interval;
      END IF;
      IF repeat_week > 0 THEN
        current_week := CEIL(extract(day from next_date) / 7);
      ELSE
        current_week := -CEIL((1 + days_in_month(next_date) - extract(day from next_date)) / 7);
      END IF;
      next_date := next_date + (repeat_week - current_week) * '7 days'::interval;
    END IF;
    EXIT WHEN next_date > range_end;

    IF next_date >= range_start AND next_date >= original_start_date THEN
      RETURN NEXT next_date;
    END IF;

    if original_end_date IS NOT NULL AND range_start >= original_start_date + (duration*intervals) AND range_start <= original_end_date + (duration*intervals) THEN
      RETURN NEXT next_date;
    END IF;
    intervals := intervals + 1;
  END LOOP;
END;
$$;


--
-- Name: interval_for(public.frequency); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.interval_for(recurs public.frequency) RETURNS interval
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  IF recurs = 'daily' THEN
    RETURN '1 day'::interval;
  ELSIF recurs = 'weekly' THEN
    RETURN '7 days'::interval;
  ELSIF recurs = 'monthly' THEN
    RETURN '1 month'::interval;
  ELSIF recurs = 'yearly' THEN
    RETURN '1 year'::interval;
  ELSE
    RAISE EXCEPTION 'Recurrence % not supported by generate_recurrences()', recurs;
  END IF;
END;
$$;


--
-- Name: intervals_between(date, date, interval); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.intervals_between(start_date date, end_date date, duration interval) RETURNS double precision
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE
  count FLOAT := 0;
  multiplier INT := 512;
BEGIN
  IF start_date > end_date THEN
    RETURN 0;
  END IF;
  LOOP
    WHILE start_date + (count + multiplier) * duration < end_date LOOP
      count := count + multiplier;
    END LOOP;
    EXIT WHEN multiplier = 1;
    multiplier := multiplier / 2;
  END LOOP;
  count := count + (extract(epoch from end_date) - extract(epoch from (start_date + count * duration))) / (extract(epoch from end_date + duration) - extract(epoch from end_date))::int;
  RETURN count;
END
$$;


--
-- Name: pseudo_random_id_generator(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.pseudo_random_id_generator() RETURNS integer
    LANGUAGE plpgsql IMMUTABLE STRICT
    AS $$
                DECLARE
                    l1 int;
                    l2 int;
                    r1 int;
                    r2 int;
                    VALUE int;
                    i int:=0;
                BEGIN
                    VALUE:= nextval('pseudo_random_id_seq');
                    l1:= (VALUE >> 16) & 65535;
                    r1:= VALUE & 65535;
                    WHILE i < 3 LOOP
                        l2 := r1;
                        r2 := l1 # ((((1366 * r1 + 150889) % 714025) / 714025.0) * 32767)::int;
                        l1 := l2;
                        r1 := r2;
                        i := i + 1;
                    END LOOP;
                    RETURN ((r1 << 16) + l1);
                END;
            $$;


--
-- Name: random_id_generator(character varying, bigint); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.random_id_generator(table_name character varying, initial_range bigint) RETURNS bigint
    LANGUAGE plpgsql
    AS $$DECLARE
              rand_int INTEGER;
              count INTEGER := 1;
              statement TEXT;
            BEGIN
              WHILE count > 0 LOOP
                initial_range := initial_range * 10;

                rand_int := (RANDOM() * initial_range)::BIGINT + initial_range / 10;

                statement := CONCAT('SELECT count(id) FROM ', table_name, ' WHERE id = ', rand_int);

                EXECUTE statement;
                IF NOT FOUND THEN
                  count := 0;
                END IF;

              END LOOP;
              RETURN rand_int;
            END;
            $$;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: event_occurrence; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.event_occurrence (
    id integer NOT NULL,
    space_id integer NOT NULL,
    event_id integer NOT NULL,
    rule text,
    starts_on date,
    ends_on date,
    starts_at timestamp without time zone,
    ends_at timestamp without time zone,
    frequency public.frequency,
    separation integer DEFAULT 1 NOT NULL,
    count integer,
    until date,
    timezone_name text DEFAULT 'Etc/UTC'::text NOT NULL,
    status integer DEFAULT 1 NOT NULL,
    CONSTRAINT positive_separation CHECK ((separation > 0))
);


--
-- Name: recurrences_for(public.event_occurrence, timestamp without time zone, timestamp without time zone); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.recurrences_for(event public.event_occurrence, range_start timestamp without time zone, range_end timestamp without time zone) RETURNS SETOF date
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
  recurrence event_occurrence_recurrence;
  recurrences_start DATE := COALESCE(event.starts_at::date, event.starts_on);
  recurrences_end DATE := range_end;
  duration INTERVAL := interval_for(event.frequency) * event.separation;
  next_date DATE;
BEGIN
  IF event.until IS NOT NULL AND event.until < recurrences_end THEN
    recurrences_end := event.until;
  END IF;
  IF event.count IS NOT NULL AND recurrences_start + (event.count - 1) * duration < recurrences_end THEN
    recurrences_end := recurrences_start + (event.count - 1) * duration;
  END IF;

  FOR recurrence IN
    SELECT event_occurrence_recurrence.*
      FROM (SELECT NULL) AS foo
      LEFT JOIN event_occurrence_recurrence
        ON event_occurrence_id = event.id
  LOOP
    FOR next_date IN
      SELECT *
        FROM generate_recurrences(
          duration,
          recurrences_start,
          COALESCE(event.ends_at::date, event.ends_on),
          range_start::date,
          recurrences_end,
          recurrence.month,
          recurrence.week,
          recurrence.day
        )
    LOOP
      RETURN NEXT next_date;
    END LOOP;
  END LOOP;
  RETURN;
END;
$$;


--
-- Name: recurring_event_occurrence_for(timestamp without time zone, timestamp without time zone, character varying, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.recurring_event_occurrence_for(range_start timestamp without time zone, range_end timestamp without time zone, time_zone character varying, event_occurrence_limit integer) RETURNS SETOF public.event_occurrence
    LANGUAGE plpgsql STABLE
    AS $$
            DECLARE
              event event_occurrence;
              original_date DATE;
              original_date_in_zone DATE;
              start_time TIME;
              start_time_in_zone TIME;
              next_date DATE;
              next_time_in_zone TIME;
              duration INTERVAL;
              time_offset INTERVAL;
              r_start DATE := (timezone('UTC', range_start) AT TIME ZONE time_zone)::DATE;
              r_end DATE := (timezone('UTC', range_end) AT TIME ZONE time_zone)::DATE;

              recurrences_start DATE := CASE WHEN r_start < range_start THEN r_start ELSE range_start END;
              recurrences_end DATE := CASE WHEN r_end > range_end THEN r_end ELSE range_end END;

              inc_interval INTERVAL := '2 hours'::INTERVAL;

              ext_start TIMESTAMP := range_start::TIMESTAMP - inc_interval;
              ext_end   TIMESTAMP := range_end::TIMESTAMP   + inc_interval;
            BEGIN
              FOR event IN
                SELECT *
                  FROM event_occurrence
                  WHERE
                    status > 0
                    AND
                    (
                      (frequency = 'once' AND
                      ((starts_on IS NOT NULL AND ends_on IS NOT NULL AND starts_on <= r_end AND ends_on >= r_start) OR
                       (starts_on IS NOT NULL AND starts_on <= r_end AND starts_on >= r_start) OR
                       (starts_at <= range_end AND ends_at >= range_start)))

                      OR

                      (
                        frequency <> 'once' AND
                        (
                          ( starts_on IS NOT NULL AND starts_on <= ext_end ) OR
                          ( starts_at IS NOT NULL AND starts_at <= ext_end )
                        ) AND (
                          (until IS NULL AND ends_at IS NULL AND ends_on IS NULL) OR
                          (until IS NOT NULL AND until >= ext_start) OR
                          (ends_on IS NOT NULL AND ends_on >= ext_start) OR
                          (ends_at IS NOT NULL AND ends_at >= ext_start)
                        )
                      )
                    )

              LOOP
                IF event.frequency = 'once' THEN
                  RETURN NEXT event;
                  CONTINUE;
                END IF;

                -- All-day event
                IF event.starts_on IS NOT NULL AND event.ends_on IS NULL THEN
                  original_date := event.starts_on;
                  duration := '1 day'::interval;
                -- Multi-day event
                ELSIF event.starts_on IS NOT NULL AND event.ends_on IS NOT NULL THEN
                  original_date := event.starts_on;
                  duration := timezone(time_zone, event.ends_on) - timezone(time_zone, event.starts_on);
                -- Timespan event
                ELSE
                  original_date := event.starts_at::date;
                  original_date_in_zone := (timezone('UTC', event.starts_at) AT TIME ZONE event.timezone_name)::date;
                  start_time := event.starts_at::time;
                  start_time_in_zone := (timezone('UTC', event.starts_at) AT time ZONE event.timezone_name)::time;
                  duration := event.ends_at - event.starts_at;
                END IF;

                IF event.count IS NOT NULL THEN
                  recurrences_start := original_date;
                END IF;

                FOR next_date IN
                  SELECT occurrence
                    FROM (
                      SELECT * FROM recurrences_for(event, recurrences_start, recurrences_end) AS occurrence
                      UNION SELECT original_date
                      LIMIT event.count
                    ) AS occurrences
                    WHERE
                      occurrence::date <= recurrences_end AND
                      (occurrence + duration)::date >= recurrences_start AND
                      occurrence NOT IN (SELECT date FROM event_occurrence_cancellation WHERE event_occurrence_id = event.id)
                    LIMIT event_occurrence_limit
                LOOP
                  -- All-day event
                  IF event.starts_on IS NOT NULL AND event.ends_on IS NULL THEN
                    CONTINUE WHEN next_date < r_start OR next_date > r_end;
                    event.starts_on := next_date;

                  -- Multi-day event
                  ELSIF event.starts_on IS NOT NULL AND event.ends_on IS NOT NULL THEN
                    event.starts_on := next_date;
                    CONTINUE WHEN event.starts_on > r_end;
                    event.ends_on := next_date + duration;
                    CONTINUE WHEN event.ends_on < r_start;

                  -- Timespan event
                  ELSE
                    next_time_in_zone := (timezone('UTC', (next_date + start_time)) at time zone event.timezone_name)::time;
                    time_offset := (original_date_in_zone + next_time_in_zone) - (original_date_in_zone + start_time_in_zone);
                    event.starts_at := next_date + start_time - time_offset;

                    CONTINUE WHEN event.starts_at > range_end;
                    event.ends_at := event.starts_at + duration;
                    CONTINUE WHEN event.ends_at < range_start;
                  END IF;

                  RETURN NEXT event;
                END LOOP;
              END LOOP;
              RETURN;
            END;
            $$;


--
-- Name: _mesoregiao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public._mesoregiao (
    gid integer NOT NULL,
    id double precision,
    nm_meso character varying(100),
    cd_geocodu character varying(2),
    geom public.geometry(MultiPolygon,4326)
);


--
-- Name: _mesoregiao_gid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public._mesoregiao_gid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: _mesoregiao_gid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public._mesoregiao_gid_seq OWNED BY public._mesoregiao.gid;


--
-- Name: _microregiao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public._microregiao (
    gid integer NOT NULL,
    id double precision,
    nm_micro character varying(100),
    cd_geocodu character varying(2),
    geom public.geometry(MultiPolygon,4326)
);


--
-- Name: _microregiao_gid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public._microregiao_gid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: _microregiao_gid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public._microregiao_gid_seq OWNED BY public._microregiao.gid;


--
-- Name: _municipios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public._municipios (
    gid integer NOT NULL,
    id double precision,
    cd_geocodm character varying(20),
    nm_municip character varying(60),
    geom public.geometry(MultiPolygon,4326)
);


--
-- Name: _municipios_gid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public._municipios_gid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: _municipios_gid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public._municipios_gid_seq OWNED BY public._municipios.gid;


--
-- Name: agent_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agent_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agent; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent (
    id integer DEFAULT nextval('public.agent_id_seq'::regclass) NOT NULL,
    parent_id integer,
    user_id integer NOT NULL,
    type smallint NOT NULL,
    name character varying(255) NOT NULL,
    location point,
    _geo_location public.geography,
    short_description text,
    long_description text,
    create_timestamp timestamp without time zone NOT NULL,
    status smallint NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    public_location boolean,
    update_timestamp timestamp(0) without time zone,
    subsite_id integer
);


--
-- Name: COLUMN agent.location; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.agent.location IS 'type=POINT';


--
-- Name: agent_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: agent_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agent_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agent_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.agent_meta_id_seq OWNED BY public.agent_meta.id;


--
-- Name: agent_relation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_relation (
    id integer NOT NULL,
    agent_id integer NOT NULL,
    object_type character varying(255) NOT NULL,
    object_id integer NOT NULL,
    type character varying(64),
    has_control boolean DEFAULT false NOT NULL,
    create_timestamp timestamp without time zone,
    status smallint
);


--
-- Name: agent_relation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agent_relation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agent_relation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.agent_relation_id_seq OWNED BY public.agent_relation.id;


--
-- Name: db_update; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.db_update (
    name character varying(255) NOT NULL,
    exec_time timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: entity_revision; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.entity_revision (
    id integer NOT NULL,
    user_id integer,
    object_id integer NOT NULL,
    object_type character varying(255) NOT NULL,
    create_timestamp timestamp(0) without time zone NOT NULL,
    action character varying(255) NOT NULL,
    message text NOT NULL
);


--
-- Name: entity_revision_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.entity_revision_data (
    id integer NOT NULL,
    "timestamp" timestamp(0) without time zone NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- Name: entity_revision_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.entity_revision_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: entity_revision_revision_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.entity_revision_revision_data (
    revision_id integer NOT NULL,
    revision_data_id integer NOT NULL
);


--
-- Name: evaluation_method_configuration; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.evaluation_method_configuration (
    id integer NOT NULL,
    opportunity_id integer NOT NULL,
    type character varying(255) NOT NULL
);


--
-- Name: evaluation_method_configuration_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.evaluation_method_configuration_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: evaluation_method_configuration_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.evaluation_method_configuration_id_seq OWNED BY public.evaluation_method_configuration.id;


--
-- Name: evaluationmethodconfiguration_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.evaluationmethodconfiguration_meta (
    id integer NOT NULL,
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- Name: evaluationmethodconfiguration_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.evaluationmethodconfiguration_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.event (
    id integer NOT NULL,
    project_id integer,
    name character varying(255) NOT NULL,
    short_description text NOT NULL,
    long_description text,
    rules text,
    create_timestamp timestamp without time zone NOT NULL,
    status smallint NOT NULL,
    agent_id integer,
    is_verified boolean DEFAULT false NOT NULL,
    type smallint NOT NULL,
    update_timestamp timestamp(0) without time zone,
    subsite_id integer
);


--
-- Name: event_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.event_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.event_id_seq OWNED BY public.event.id;


--
-- Name: event_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.event_meta (
    key character varying(255) NOT NULL,
    object_id integer NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: event_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.event_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.event_meta_id_seq OWNED BY public.event_meta.id;


--
-- Name: event_occurrence_cancellation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.event_occurrence_cancellation (
    id integer NOT NULL,
    event_occurrence_id integer,
    date date
);


--
-- Name: event_occurrence_cancellation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.event_occurrence_cancellation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event_occurrence_cancellation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.event_occurrence_cancellation_id_seq OWNED BY public.event_occurrence_cancellation.id;


--
-- Name: event_occurrence_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.event_occurrence_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event_occurrence_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.event_occurrence_id_seq OWNED BY public.event_occurrence.id;


--
-- Name: event_occurrence_recurrence; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.event_occurrence_recurrence (
    id integer NOT NULL,
    event_occurrence_id integer,
    month integer,
    day integer,
    week integer
);


--
-- Name: event_occurrence_recurrence_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.event_occurrence_recurrence_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: event_occurrence_recurrence_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.event_occurrence_recurrence_id_seq OWNED BY public.event_occurrence_recurrence.id;


--
-- Name: file_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: file; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.file (
    id integer DEFAULT nextval('public.file_id_seq'::regclass) NOT NULL,
    md5 character varying(32) NOT NULL,
    mime_type character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    object_type character varying(255) NOT NULL,
    object_id integer NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    grp character varying(32) NOT NULL,
    description character varying(255),
    parent_id integer,
    path character varying(1024) DEFAULT NULL::character varying,
    private boolean DEFAULT false NOT NULL
);


--
-- Name: geo_division_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.geo_division_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: geo_division; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.geo_division (
    id integer DEFAULT nextval('public.geo_division_id_seq'::regclass) NOT NULL,
    parent_id integer,
    type character varying(32) NOT NULL,
    cod character varying(32),
    name character varying(128) NOT NULL,
    geom public.geometry,
    CONSTRAINT enforce_dims_geom CHECK ((public.st_ndims(geom) = 2)),
    CONSTRAINT enforce_geotype_geom CHECK (((public.geometrytype(geom) = 'MULTIPOLYGON'::text) OR (geom IS NULL))),
    CONSTRAINT enforce_srid_geom CHECK ((public.st_srid(geom) = 4326))
);


--
-- Name: metadata; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.metadata (
    object_id integer NOT NULL,
    object_type character varying(255) NOT NULL,
    key character varying(32) NOT NULL,
    value text
);


--
-- Name: metalist_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.metalist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: metalist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.metalist (
    id integer DEFAULT nextval('public.metalist_id_seq'::regclass) NOT NULL,
    object_type character varying(255) NOT NULL,
    object_id integer NOT NULL,
    grp character varying(32) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    value character varying(2048) NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    "order" smallint
);


--
-- Name: notification_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notification; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notification (
    id integer DEFAULT nextval('public.notification_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    request_id integer,
    message text NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    action_timestamp timestamp without time zone,
    status smallint NOT NULL
);


--
-- Name: notification_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notification_meta (
    id integer NOT NULL,
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- Name: notification_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notification_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: occurrence_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.occurrence_id_seq
    START WITH 100000
    INCREMENT BY 1
    MINVALUE 100000
    NO MAXVALUE
    CACHE 1
    CYCLE;


--
-- Name: opportunity_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.opportunity_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: opportunity; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.opportunity (
    id integer DEFAULT nextval('public.opportunity_id_seq'::regclass) NOT NULL,
    parent_id integer,
    agent_id integer NOT NULL,
    type smallint,
    name character varying(255) NOT NULL,
    short_description text,
    long_description text,
    registration_from timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    registration_to timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    published_registrations boolean NOT NULL,
    registration_categories text,
    create_timestamp timestamp(0) without time zone NOT NULL,
    update_timestamp timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    status smallint NOT NULL,
    subsite_id integer,
    object_type character varying(255) NOT NULL,
    object_id integer NOT NULL
);


--
-- Name: opportunity_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.opportunity_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: opportunity_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.opportunity_meta (
    id integer DEFAULT nextval('public.opportunity_meta_id_seq'::regclass) NOT NULL,
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- Name: pcache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pcache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pcache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pcache (
    id integer DEFAULT nextval('public.pcache_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    action character varying(255) NOT NULL,
    create_timestamp timestamp(0) without time zone NOT NULL,
    object_type character varying(255) NOT NULL,
    object_id integer
);


--
-- Name: permission_cache_pending; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permission_cache_pending (
    id integer NOT NULL,
    object_id integer NOT NULL,
    object_type character varying(255) NOT NULL
);


--
-- Name: permission_cache_pending_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permission_cache_pending_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    short_description text,
    long_description text,
    create_timestamp timestamp without time zone NOT NULL,
    status smallint NOT NULL,
    agent_id integer,
    is_verified boolean DEFAULT false NOT NULL,
    type smallint NOT NULL,
    parent_id integer,
    registration_from timestamp without time zone,
    registration_to timestamp without time zone,
    update_timestamp timestamp(0) without time zone,
    subsite_id integer
);


--
-- Name: project_event; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_event (
    id integer NOT NULL,
    event_id integer NOT NULL,
    project_id integer NOT NULL,
    type smallint NOT NULL,
    status smallint NOT NULL
);


--
-- Name: project_event_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_event_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_event_id_seq OWNED BY public.project_event.id;


--
-- Name: project_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_id_seq OWNED BY public.project.id;


--
-- Name: project_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.project_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: project_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.project_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.project_meta_id_seq OWNED BY public.project_meta.id;


--
-- Name: pseudo_random_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pseudo_random_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registration (
    id integer DEFAULT public.pseudo_random_id_generator() NOT NULL,
    opportunity_id integer NOT NULL,
    category character varying(255),
    agent_id integer NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    sent_timestamp timestamp without time zone,
    status smallint NOT NULL,
    agents_data text,
    subsite_id integer,
    consolidated_result character varying(255) DEFAULT NULL::character varying,
    number character varying(24),
    valuers_exceptions_list text DEFAULT '{"include": [], "exclude": []}'::text NOT NULL
);


--
-- Name: registration_evaluation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registration_evaluation (
    id integer NOT NULL,
    registration_id integer DEFAULT public.pseudo_random_id_generator() NOT NULL,
    user_id integer NOT NULL,
    result character varying(255) DEFAULT NULL::character varying,
    evaluation_data text NOT NULL,
    status smallint
);


--
-- Name: registration_evaluation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registration_evaluation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration_field_configuration; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registration_field_configuration (
    id integer NOT NULL,
    opportunity_id integer,
    title character varying(255) NOT NULL,
    description text,
    categories text,
    required boolean NOT NULL,
    field_type character varying(255) NOT NULL,
    field_options text NOT NULL,
    max_size text,
    display_order smallint DEFAULT 255
);


--
-- Name: COLUMN registration_field_configuration.categories; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.registration_field_configuration.categories IS '(DC2Type:array)';


--
-- Name: registration_field_configuration_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registration_field_configuration_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration_file_configuration; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registration_file_configuration (
    id integer NOT NULL,
    opportunity_id integer,
    title character varying(255) NOT NULL,
    description text,
    required boolean NOT NULL,
    categories text,
    display_order smallint DEFAULT 255
);


--
-- Name: COLUMN registration_file_configuration.categories; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.registration_file_configuration.categories IS '(DC2Type:array)';


--
-- Name: registration_file_configuration_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registration_file_configuration_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration_file_configuration_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.registration_file_configuration_id_seq OWNED BY public.registration_file_configuration.id;


--
-- Name: registration_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registration_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.registration_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: registration_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.registration_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: registration_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.registration_meta_id_seq OWNED BY public.registration_meta.id;


--
-- Name: request_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: request; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.request (
    id integer DEFAULT nextval('public.request_id_seq'::regclass) NOT NULL,
    request_uid character varying(32) NOT NULL,
    requester_user_id integer NOT NULL,
    origin_type character varying(255) NOT NULL,
    origin_id integer NOT NULL,
    destination_type character varying(255) NOT NULL,
    destination_id integer NOT NULL,
    metadata text,
    type character varying(255) NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    action_timestamp timestamp without time zone,
    status smallint NOT NULL
);


--
-- Name: revision_data_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revision_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role (
    id integer NOT NULL,
    usr_id integer,
    name character varying(32) NOT NULL,
    subsite_id integer
);


--
-- Name: role_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_id_seq OWNED BY public.role.id;


--
-- Name: seal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seal (
    id integer NOT NULL,
    agent_id integer NOT NULL,
    name character varying(255) NOT NULL,
    short_description text,
    long_description text,
    valid_period smallint NOT NULL,
    create_timestamp timestamp(0) without time zone NOT NULL,
    status smallint NOT NULL,
    certificate_text text,
    update_timestamp timestamp(0) without time zone,
    subsite_id integer
);


--
-- Name: seal_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: seal_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seal_meta (
    id integer NOT NULL,
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- Name: seal_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seal_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: seal_relation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seal_relation (
    id integer NOT NULL,
    seal_id integer,
    object_id integer NOT NULL,
    create_timestamp timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    status smallint,
    object_type character varying(255) NOT NULL,
    agent_id integer NOT NULL,
    owner_id integer,
    validate_date date,
    renovation_request boolean
);


--
-- Name: seal_relation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seal_relation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: space; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.space (
    id integer NOT NULL,
    parent_id integer,
    location point,
    _geo_location public.geography,
    name character varying(255) NOT NULL,
    short_description text,
    long_description text,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    status smallint NOT NULL,
    type smallint NOT NULL,
    agent_id integer,
    is_verified boolean DEFAULT false NOT NULL,
    public boolean DEFAULT false NOT NULL,
    update_timestamp timestamp(0) without time zone,
    subsite_id integer
);


--
-- Name: COLUMN space.location; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.space.location IS 'type=POINT';


--
-- Name: space_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.space_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: space_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.space_id_seq OWNED BY public.space.id;


--
-- Name: space_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.space_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: space_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.space_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: space_meta_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.space_meta_id_seq OWNED BY public.space_meta.id;


--
-- Name: subsite; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subsite (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    create_timestamp timestamp(0) without time zone NOT NULL,
    status smallint NOT NULL,
    agent_id integer NOT NULL,
    url character varying(255) NOT NULL,
    namespace character varying(50) NOT NULL,
    alias_url character varying(255) DEFAULT NULL::character varying,
    verified_seals character varying(512) DEFAULT '[]'::character varying
);


--
-- Name: subsite_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subsite_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subsite_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subsite_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: subsite_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subsite_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: term; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.term (
    id integer NOT NULL,
    taxonomy character varying(64) NOT NULL,
    term character varying(255) NOT NULL,
    description text
);


--
-- Name: COLUMN term.taxonomy; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.term.taxonomy IS '1=tag';


--
-- Name: term_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.term_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: term_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.term_id_seq OWNED BY public.term.id;


--
-- Name: term_relation; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.term_relation (
    term_id integer NOT NULL,
    object_type character varying(255) NOT NULL,
    object_id integer NOT NULL,
    id integer NOT NULL
);


--
-- Name: term_relation_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.term_relation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: term_relation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.term_relation_id_seq OWNED BY public.term_relation.id;


--
-- Name: user_app; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_app (
    public_key character varying(64) NOT NULL,
    private_key character varying(128) NOT NULL,
    user_id integer NOT NULL,
    name text NOT NULL,
    status integer NOT NULL,
    create_timestamp timestamp without time zone NOT NULL,
    subsite_id integer
);


--
-- Name: user_meta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_meta (
    object_id integer NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    id integer NOT NULL
);


--
-- Name: user_meta_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_meta_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usr_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usr; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usr (
    id integer DEFAULT nextval('public.usr_id_seq'::regclass) NOT NULL,
    auth_provider smallint NOT NULL,
    auth_uid character varying(512) NOT NULL,
    email character varying(255) NOT NULL,
    last_login_timestamp timestamp without time zone NOT NULL,
    create_timestamp timestamp without time zone DEFAULT now() NOT NULL,
    status smallint NOT NULL,
    profile_id integer
);


--
-- Name: COLUMN usr.auth_provider; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.usr.auth_provider IS '1=openid';


--
-- Name: _mesoregiao gid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._mesoregiao ALTER COLUMN gid SET DEFAULT nextval('public._mesoregiao_gid_seq'::regclass);


--
-- Name: _microregiao gid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._microregiao ALTER COLUMN gid SET DEFAULT nextval('public._microregiao_gid_seq'::regclass);


--
-- Name: _municipios gid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._municipios ALTER COLUMN gid SET DEFAULT nextval('public._municipios_gid_seq'::regclass);


--
-- Name: agent_relation id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_relation ALTER COLUMN id SET DEFAULT nextval('public.agent_relation_id_seq'::regclass);


--
-- Name: evaluation_method_configuration id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.evaluation_method_configuration ALTER COLUMN id SET DEFAULT nextval('public.evaluation_method_configuration_id_seq'::regclass);


--
-- Name: event id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event ALTER COLUMN id SET DEFAULT nextval('public.event_id_seq'::regclass);


--
-- Name: event_occurrence id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence ALTER COLUMN id SET DEFAULT nextval('public.event_occurrence_id_seq'::regclass);


--
-- Name: event_occurrence_cancellation id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_cancellation ALTER COLUMN id SET DEFAULT nextval('public.event_occurrence_cancellation_id_seq'::regclass);


--
-- Name: event_occurrence_recurrence id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_recurrence ALTER COLUMN id SET DEFAULT nextval('public.event_occurrence_recurrence_id_seq'::regclass);


--
-- Name: project id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project ALTER COLUMN id SET DEFAULT nextval('public.project_id_seq'::regclass);


--
-- Name: project_event id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_event ALTER COLUMN id SET DEFAULT nextval('public.project_event_id_seq'::regclass);


--
-- Name: space id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space ALTER COLUMN id SET DEFAULT nextval('public.space_id_seq'::regclass);


--
-- Name: term id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.term ALTER COLUMN id SET DEFAULT nextval('public.term_id_seq'::regclass);


--
-- Name: term_relation id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.term_relation ALTER COLUMN id SET DEFAULT nextval('public.term_relation_id_seq'::regclass);


--
-- Data for Name: _mesoregiao; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public._mesoregiao (gid, id, nm_meso, cd_geocodu, geom) FROM stdin;
\.


--
-- Data for Name: _microregiao; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public._microregiao (gid, id, nm_micro, cd_geocodu, geom) FROM stdin;
\.


--
-- Data for Name: _municipios; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public._municipios (gid, id, cd_geocodm, nm_municip, geom) FROM stdin;
\.


--
-- Data for Name: agent; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.agent (id, parent_id, user_id, type, name, location, _geo_location, short_description, long_description, create_timestamp, status, is_verified, public_location, update_timestamp, subsite_id) FROM stdin;
6	1	1	2	vamos ver (modificado asdasdasd ok)	(0,0)	0101000020E610000000000000000000000000000000000000	resumo é obruigatório	asdasd asd asd	2019-06-24 21:38:38	1	f	f	2019-06-25 21:05:07	\N
5	1	1	1	teste	(-46.6469345087733984,-23.5466985999999991)	0101000020E6100000F4EAFEBFCE5247C0DCC47F70F48B37C0	asd asd  d	asd asd	2019-06-24 20:49:12	1	f	f	2019-06-26 20:46:12	\N
1	\N	1	1	Administrador do Sistema ok	(0,0)	0101000020E610000000000000000000000000000000000000	o resumo, ou descrição curta, é obrigatória		2019-03-07 00:00:00	1	f	f	2019-07-02 20:15:49	\N
9	1	1	1	teste	(0,0)	0101000020E610000000000000000000000000000000000000	asd asd asd a		2019-07-16 00:41:32	1	f	f	\N	\N
7	\N	1	1	um teste de novo agente	(0,0)	\N	asd asd asd asd asd 	asd asd asd asd a	2019-06-24 22:14:47	1	f	f	2019-06-24 23:02:49	\N
\.


--
-- Data for Name: agent_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.agent_meta (object_id, key, value, id) FROM stdin;
5	localizacao		1
5	instagram		2
7	emailPublico	rafael@hacklab.com.br	3
7	telefonePublico	(11) 96465-5828	4
7	site	https://hacklab.com.br	5
7	instagram	@rafachaves	6
5	emailPublico	rafael@hacklab.com.br	8
1	emailPublico		7
\.


--
-- Data for Name: agent_relation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.agent_relation (id, agent_id, object_type, object_id, type, has_control, create_timestamp, status) FROM stdin;
\.


--
-- Data for Name: db_update; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.db_update (name, exec_time) FROM stdin;
alter tablel term taxonomy type	2019-03-07 23:54:06.885661
new random id generator	2019-03-07 23:54:06.885661
migrate gender	2019-03-07 23:54:06.885661
create table user apps	2019-03-07 23:54:06.885661
create table user_meta	2019-03-07 23:54:06.885661
create seal and seal relation tables	2019-03-07 23:54:06.885661
resize entity meta key columns	2019-03-07 23:54:06.885661
create registration field configuration table	2019-03-07 23:54:06.885661
alter table registration_file_configuration add categories	2019-03-07 23:54:06.885661
create saas tables	2019-03-07 23:54:06.885661
rename saas tables to subsite	2019-03-07 23:54:06.885661
remove parent_url and add alias_url	2019-03-07 23:54:06.885661
verified seal migration	2019-03-07 23:54:06.885661
create update timestamp entities	2019-03-07 23:54:06.885661
alter table role add column subsite_id	2019-03-07 23:54:06.885661
Fix field options field type from registration field configuration	2019-03-07 23:54:06.885661
ADD columns subsite_id	2019-03-07 23:54:06.885661
remove subsite slug column	2019-03-07 23:54:06.885661
add subsite verified_seals column	2019-03-07 23:54:06.885661
update entities last_update_timestamp with user last log timestamp	2019-03-07 23:54:06.885661
Created owner seal relation field	2019-03-07 23:54:06.885661
create table pcache	2019-03-07 23:54:06.885661
function create pcache id sequence 2	2019-03-07 23:54:06.885661
Add field for maximum size from registration field configuration	2019-03-07 23:54:06.885661
Add notification type for compliant and suggestion messages	2019-03-07 23:54:06.885661
create entity revision tables	2019-03-07 23:54:06.885661
ALTER TABLE file ADD COLUMN path	2019-03-07 23:54:06.885661
*_meta drop all indexes again	2019-03-07 23:54:06.885661
recreate *_meta indexes	2019-03-07 23:54:06.885661
create permission cache pending table2	2019-03-07 23:54:06.885661
create opportunity tables	2019-03-07 23:54:06.885661
DROP CONSTRAINT registration_project_fk");	2019-03-07 23:54:06.885661
fix opportunity parent FK	2019-03-07 23:54:06.885661
fix opportunity type 35	2019-03-07 23:54:06.885661
create opportunity sequence	2019-03-07 23:54:06.885661
update opportunity_meta_id sequence	2019-03-07 23:54:06.885661
rename opportunity_meta key isProjectPhase to isOpportunityPhase	2019-03-07 23:54:06.885661
migrate introInscricoes value to shortDescription	2019-03-07 23:54:06.885661
ALTER TABLE registration ADD consolidated_result	2019-03-07 23:54:06.885661
create evaluation methods tables	2019-03-07 23:54:06.885661
create registration_evaluation table	2019-03-07 23:54:06.885661
ALTER TABLE opportunity ALTER type DROP NOT NULL;	2019-03-07 23:54:06.885661
create seal relation renovation flag field	2019-03-07 23:54:06.885661
create seal relation validate date	2019-03-07 23:54:06.885661
update seal_relation set validate_date	2019-03-07 23:54:06.885661
refactor of entity meta keky value indexes	2019-03-07 23:54:06.885661
DROP index registration_meta_value_idx	2019-03-07 23:54:06.885661
altertable registration_file_and_files_add_order	2019-03-07 23:54:06.885661
replace subsite entidades_habilitadas values	2019-03-07 23:54:06.885661
replace subsite cor entidades values	2019-03-07 23:54:06.885661
ALTER TABLE file ADD private and update	2019-03-07 23:54:06.885661
move private files	2019-03-07 23:54:06.885661
create permission cache sequence	2019-03-07 23:54:06.885661
create evaluation methods sequence	2019-03-07 23:54:06.885661
change opportunity field agent_id not null	2019-03-07 23:54:06.885661
alter table registration add column number	2019-03-07 23:54:06.885661
update registrations set number fixed	2019-03-07 23:54:06.885661
alter table registration add column valuers_exceptions_list	2019-03-07 23:54:06.885661
update taxonomy slug tag	2019-03-07 23:54:06.885661
update taxonomy slug area	2019-03-07 23:54:06.885661
update taxonomy slug linguagem	2019-03-07 23:54:06.885661
recreate pcache	2019-03-07 23:54:19.344941
generate file path	2019-03-07 23:54:19.352266
create entities history entries	2019-03-07 23:54:19.357385
create entities updated revision	2019-03-07 23:54:19.362878
fix update timestamp of revisioned entities	2019-03-07 23:54:19.367904
consolidate registration result	2019-03-07 23:54:19.3728
create avatar thumbs	2019-03-07 23:55:16.963658
\.


--
-- Data for Name: entity_revision; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.entity_revision (id, user_id, object_id, object_type, create_timestamp, action, message) FROM stdin;
1	1	1	MapasCulturais\\Entities\\Agent	2019-03-07 00:00:00	created	Registro criado.
2	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:49:12	created	Registro criado.
3	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:51:08	modified	Registro atualizado.
4	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:51:33	modified	Registro atualizado.
5	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:51:38	modified	Registro atualizado.
6	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:51:45	modified	Registro atualizado.
7	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:52:03	modified	Registro atualizado.
8	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 20:56:55	modified	Registro atualizado.
9	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:01:24	modified	Registro atualizado.
10	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:01:28	modified	Registro atualizado.
11	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:01:32	modified	Registro atualizado.
12	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:01:36	modified	Registro atualizado.
13	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:01:59	modified	Registro atualizado.
14	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:02:11	modified	Registro atualizado.
15	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:03:28	modified	Registro atualizado.
16	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:03:36	modified	Registro atualizado.
17	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:04:15	modified	Registro atualizado.
18	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:18:03	modified	Registro atualizado.
19	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:18:14	modified	Registro atualizado.
20	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:18:14	modified	Registro atualizado.
21	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:20:55	modified	Registro atualizado.
22	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:20:56	modified	Registro atualizado.
23	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:37:47	modified	Registro atualizado.
24	1	6	MapasCulturais\\Entities\\Agent	2019-06-24 21:38:38	created	Registro criado.
25	1	6	MapasCulturais\\Entities\\Agent	2019-06-24 21:41:24	modified	Registro atualizado.
26	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:43:14	modified	Registro atualizado.
27	1	5	MapasCulturais\\Entities\\Agent	2019-06-24 21:43:15	modified	Registro atualizado.
28	1	6	MapasCulturais\\Entities\\Agent	2019-06-24 21:43:15	modified	Registro atualizado.
29	1	6	MapasCulturais\\Entities\\Agent	2019-06-24 21:43:15	modified	Registro atualizado.
30	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 22:14:47	created	Registro criado.
31	1	6	MapasCulturais\\Entities\\Agent	2019-06-24 22:36:05	modified	Registro atualizado.
32	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 22:48:06	modified	Registro atualizado.
33	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 23:00:46	modified	Registro atualizado.
34	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 23:00:57	modified	Registro atualizado.
35	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 23:02:15	modified	Registro atualizado.
36	1	7	MapasCulturais\\Entities\\Agent	2019-06-24 23:02:49	modified	Registro atualizado.
37	1	6	MapasCulturais\\Entities\\Agent	2019-06-25 14:25:45	modified	Registro atualizado.
38	1	5	MapasCulturais\\Entities\\Agent	2019-06-25 14:55:09	modified	Registro atualizado.
39	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 20:39:07	modified	Registro atualizado.
40	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 20:39:43	modified	Registro atualizado.
41	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 20:52:03	modified	Registro atualizado.
42	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 20:52:44	modified	Registro atualizado.
43	1	6	MapasCulturais\\Entities\\Agent	2019-06-25 20:53:43	modified	Registro atualizado.
44	1	6	MapasCulturais\\Entities\\Agent	2019-06-25 21:04:56	modified	Registro atualizado.
45	1	6	MapasCulturais\\Entities\\Agent	2019-06-25 21:05:07	modified	Registro atualizado.
46	1	1	MapasCulturais\\Entities\\Event	2019-06-25 21:47:54	created	Registro criado.
47	1	1	MapasCulturais\\Entities\\Event	2019-06-25 21:57:38	modified	Registro atualizado.
48	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 22:31:32	modified	Registro atualizado.
49	1	5	MapasCulturais\\Entities\\Agent	2019-06-25 22:31:50	modified	Registro atualizado.
50	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 22:51:44	modified	Registro atualizado.
51	1	1	MapasCulturais\\Entities\\Agent	2019-06-25 22:51:56	modified	Registro atualizado.
52	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:03:56	modified	Registro atualizado.
53	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:04:54	modified	Registro atualizado.
54	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:05:31	modified	Registro atualizado.
55	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:06:09	modified	Registro atualizado.
56	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:06:21	modified	Registro atualizado.
57	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:07:30	modified	Registro atualizado.
58	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:09:47	modified	Registro atualizado.
59	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:11:26	modified	Registro atualizado.
60	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:11:56	modified	Registro atualizado.
61	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:13:16	modified	Registro atualizado.
62	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:21:24	modified	Registro atualizado.
63	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:22:02	modified	Registro atualizado.
64	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:23:05	modified	Registro atualizado.
65	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:24:10	modified	Registro atualizado.
66	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:24:30	modified	Registro atualizado.
67	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:24:41	modified	Registro atualizado.
68	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:27:28	modified	Registro atualizado.
69	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:27:44	modified	Registro atualizado.
70	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:28:55	modified	Registro atualizado.
71	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:29:08	modified	Registro atualizado.
72	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:30:21	modified	Registro atualizado.
73	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:31:47	modified	Registro atualizado.
74	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:33:36	modified	Registro atualizado.
75	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:34:07	modified	Registro atualizado.
76	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:35:06	modified	Registro atualizado.
77	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:35:18	modified	Registro atualizado.
78	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:35:41	modified	Registro atualizado.
79	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:35:47	modified	Registro atualizado.
80	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:35:57	modified	Registro atualizado.
81	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:36:05	modified	Registro atualizado.
82	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:36:15	modified	Registro atualizado.
83	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:36:50	modified	Registro atualizado.
84	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:37:10	modified	Registro atualizado.
85	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:41:34	modified	Registro atualizado.
86	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:41:46	modified	Registro atualizado.
87	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:41:52	modified	Registro atualizado.
88	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:52:51	modified	Registro atualizado.
89	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 18:53:17	modified	Registro atualizado.
90	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:01:38	modified	Registro atualizado.
91	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:19:23	modified	Registro atualizado.
92	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:21:15	modified	Registro atualizado.
93	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:21:28	modified	Registro atualizado.
94	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:27:45	modified	Registro atualizado.
95	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:28:23	modified	Registro atualizado.
96	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:28:47	modified	Registro atualizado.
97	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:29:12	modified	Registro atualizado.
98	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:32:26	modified	Registro atualizado.
99	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:32:41	modified	Registro atualizado.
100	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:32:50	modified	Registro atualizado.
101	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:55:37	modified	Registro atualizado.
102	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 19:55:43	modified	Registro atualizado.
103	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 20:45:13	modified	Registro atualizado.
104	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 20:45:40	modified	Registro atualizado.
105	1	5	MapasCulturais\\Entities\\Agent	2019-06-26 20:46:12	modified	Registro atualizado.
106	1	1	MapasCulturais\\Entities\\Space	2019-06-28 18:05:52	created	Registro criado.
107	1	1	MapasCulturais\\Entities\\Space	2019-06-28 18:06:06	modified	Registro atualizado.
108	1	1	MapasCulturais\\Entities\\Space	2019-06-28 19:17:59	modified	Registro atualizado.
109	1	1	MapasCulturais\\Entities\\Space	2019-06-28 19:18:16	modified	Registro atualizado.
110	1	2	MapasCulturais\\Entities\\Event	2019-07-02 21:07:34	created	Registro criado.
111	1	2	MapasCulturais\\Entities\\Event	2019-07-04 19:27:32	modified	Registro atualizado.
112	1	3	MapasCulturais\\Entities\\Event	2019-07-05 15:02:26	created	Registro criado.
113	1	1	MapasCulturais\\Entities\\Space	2019-07-05 19:23:59	modified	Registro atualizado.
114	1	1	MapasCulturais\\Entities\\Space	2019-07-05 19:24:52	modified	Registro atualizado.
115	1	5	MapasCulturais\\Entities\\Event	2019-07-05 20:53:18	created	Registro criado.
116	1	5	MapasCulturais\\Entities\\Event	2019-07-05 21:03:56	modified	Registro atualizado.
117	1	2	MapasCulturais\\Entities\\Space	2019-07-05 21:20:29	created	Registro criado.
118	1	2	MapasCulturais\\Entities\\Space	2019-07-05 21:20:35	modified	Registro atualizado.
119	1	3	MapasCulturais\\Entities\\Space	2019-07-05 21:32:32	created	Registro criado.
120	1	3	MapasCulturais\\Entities\\Space	2019-07-05 21:32:44	modified	Registro atualizado.
121	1	3	MapasCulturais\\Entities\\Space	2019-07-05 21:33:16	modified	Registro atualizado.
122	1	6	MapasCulturais\\Entities\\Event	2019-07-05 21:34:30	created	Registro criado.
123	1	6	MapasCulturais\\Entities\\Event	2019-07-05 21:35:58	modified	Registro atualizado.
124	1	6	MapasCulturais\\Entities\\Event	2019-07-05 21:39:03	modified	Registro atualizado.
125	1	6	MapasCulturais\\Entities\\Event	2019-07-05 21:44:46	modified	Registro atualizado.
126	1	3	MapasCulturais\\Entities\\Space	2019-07-10 17:37:00	modified	Registro atualizado.
127	1	2	MapasCulturais\\Entities\\Space	2019-07-10 22:22:12	modified	Registro atualizado.
128	1	3	MapasCulturais\\Entities\\Space	2019-07-13 21:08:58	modified	Registro atualizado.
129	1	1	MapasCulturais\\Entities\\Space	2019-07-13 21:23:15	modified	Registro atualizado.
130	1	9	MapasCulturais\\Entities\\Agent	2019-07-16 00:41:32	created	Registro criado.
131	1	6	MapasCulturais\\Entities\\Event	2019-07-16 13:34:51	modified	Registro atualizado.
\.


--
-- Data for Name: entity_revision_data; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.entity_revision_data (id, "timestamp", key, value) FROM stdin;
1	2019-03-07 23:54:19	_type	1
2	2019-03-07 23:54:19	name	"Admin@local"
3	2019-03-07 23:54:19	publicLocation	null
4	2019-03-07 23:54:19	location	{"latitude":0,"longitude":0}
5	2019-03-07 23:54:19	shortDescription	null
6	2019-03-07 23:54:19	longDescription	null
7	2019-03-07 23:54:19	createTimestamp	{"date":"2019-03-07 00:00:00.000000","timezone_type":3,"timezone":"UTC"}
8	2019-03-07 23:54:19	status	1
9	2019-03-07 23:54:19	updateTimestamp	{"date":"2019-03-07 00:00:00.000000","timezone_type":3,"timezone":"UTC"}
10	2019-03-07 23:54:19	_subsiteId	null
11	2019-06-24 20:49:13	_type	1
12	2019-06-24 20:49:13	name	"teste"
13	2019-06-24 20:49:13	publicLocation	true
14	2019-06-24 20:49:13	location	{"latitude":"0","longitude":"0"}
15	2019-06-24 20:49:13	shortDescription	"asd asd  d"
16	2019-06-24 20:49:13	longDescription	"asd"
17	2019-06-24 20:49:13	createTimestamp	{"date":"2019-06-24 20:49:12.000000","timezone_type":3,"timezone":"UTC"}
18	2019-06-24 20:49:13	status	1
19	2019-06-24 20:49:13	updateTimestamp	null
20	2019-06-24 20:49:13	_subsiteId	null
21	2019-06-24 20:49:13	_terms	{"":["Arte Digital"]}
22	2019-06-24 20:51:08	updateTimestamp	{"date":"2019-06-24 20:51:08.000000","timezone_type":3,"timezone":"UTC"}
23	2019-06-24 20:51:08	localizacao	"P\\u00fablica"
24	2019-06-24 20:51:33	longDescription	"asd asd"
25	2019-06-24 20:51:33	updateTimestamp	{"date":"2019-06-24 20:51:33.000000","timezone_type":3,"timezone":"UTC"}
26	2019-06-24 20:51:38	updateTimestamp	{"date":"2019-06-24 20:51:38.000000","timezone_type":3,"timezone":"UTC"}
27	2019-06-24 20:51:45	updateTimestamp	{"date":"2019-06-24 20:51:45.000000","timezone_type":3,"timezone":"UTC"}
28	2019-06-24 20:51:45	localizacao	""
29	2019-06-24 20:52:03	updateTimestamp	{"date":"2019-06-24 20:52:03.000000","timezone_type":3,"timezone":"UTC"}
30	2019-06-24 20:56:55	updateTimestamp	{"date":"2019-06-24 20:56:55.000000","timezone_type":3,"timezone":"UTC"}
31	2019-06-24 21:01:24	publicLocation	false
32	2019-06-24 21:01:24	updateTimestamp	{"date":"2019-06-24 21:01:24.000000","timezone_type":3,"timezone":"UTC"}
33	2019-06-24 21:01:28	updateTimestamp	{"date":"2019-06-24 21:01:28.000000","timezone_type":3,"timezone":"UTC"}
34	2019-06-24 21:01:32	updateTimestamp	{"date":"2019-06-24 21:01:32.000000","timezone_type":3,"timezone":"UTC"}
35	2019-06-24 21:01:36	publicLocation	true
36	2019-06-24 21:01:36	updateTimestamp	{"date":"2019-06-24 21:01:36.000000","timezone_type":3,"timezone":"UTC"}
37	2019-06-24 21:01:59	updateTimestamp	{"date":"2019-06-24 21:01:59.000000","timezone_type":3,"timezone":"UTC"}
38	2019-06-24 21:02:11	publicLocation	false
39	2019-06-24 21:02:11	updateTimestamp	{"date":"2019-06-24 21:02:11.000000","timezone_type":3,"timezone":"UTC"}
40	2019-06-24 21:03:28	publicLocation	true
41	2019-06-24 21:03:28	updateTimestamp	{"date":"2019-06-24 21:03:28.000000","timezone_type":3,"timezone":"UTC"}
42	2019-06-24 21:03:36	publicLocation	false
43	2019-06-24 21:03:36	updateTimestamp	{"date":"2019-06-24 21:03:36.000000","timezone_type":3,"timezone":"UTC"}
44	2019-06-24 21:04:15	updateTimestamp	{"date":"2019-06-24 21:04:15.000000","timezone_type":3,"timezone":"UTC"}
45	2019-06-24 21:04:15	instagram	"@rafachaves"
46	2019-06-24 21:18:03	updateTimestamp	{"date":"2019-06-24 21:18:03.000000","timezone_type":3,"timezone":"UTC"}
47	2019-06-24 21:18:03	instagram	""
48	2019-06-24 21:18:14	updateTimestamp	{"date":"2019-06-24 21:18:14.000000","timezone_type":3,"timezone":"UTC"}
49	2019-06-24 21:18:14	instagram	"@rafachaves"
50	2019-06-24 21:18:14	instagram	""
51	2019-06-24 21:20:55	updateTimestamp	{"date":"2019-06-24 21:20:55.000000","timezone_type":3,"timezone":"UTC"}
52	2019-06-24 21:20:56	updateTimestamp	{"date":"2019-06-24 21:20:56.000000","timezone_type":3,"timezone":"UTC"}
53	2019-06-24 21:37:47	updateTimestamp	{"date":"2019-06-24 21:37:47.000000","timezone_type":3,"timezone":"UTC"}
54	2019-06-24 21:38:38	_type	1
55	2019-06-24 21:38:38	name	"vamos ver"
56	2019-06-24 21:38:38	publicLocation	true
57	2019-06-24 21:38:38	location	{"latitude":"0","longitude":"0"}
58	2019-06-24 21:38:38	shortDescription	"resumo \\u00e9 obruigat\\u00f3rio"
59	2019-06-24 21:38:38	longDescription	"asdasd asd asd"
60	2019-06-24 21:38:38	createTimestamp	{"date":"2019-06-24 21:38:38.000000","timezone_type":3,"timezone":"UTC"}
61	2019-06-24 21:38:38	status	1
62	2019-06-24 21:38:38	updateTimestamp	null
63	2019-06-24 21:38:38	_subsiteId	null
64	2019-06-24 21:38:38	_terms	{"":["Arquitetura-Urbanismo"]}
65	2019-06-24 21:41:24	name	"vamos ver (modificado)"
66	2019-06-24 21:41:24	updateTimestamp	{"date":"2019-06-24 21:41:24.000000","timezone_type":3,"timezone":"UTC"}
67	2019-06-24 21:43:14	updateTimestamp	{"date":"2019-06-24 21:43:14.000000","timezone_type":3,"timezone":"UTC"}
68	2019-06-24 21:43:15	updateTimestamp	{"date":"2019-06-24 21:43:15.000000","timezone_type":3,"timezone":"UTC"}
69	2019-06-24 21:43:15	name	"vamos ver"
70	2019-06-24 21:43:15	publicLocation	false
71	2019-06-24 21:43:15	updateTimestamp	{"date":"2019-06-24 21:43:15.000000","timezone_type":3,"timezone":"UTC"}
72	2019-06-24 21:43:15	name	"vamos ver (modificado)"
73	2019-06-24 21:43:15	publicLocation	true
74	2019-06-24 22:14:47	_type	1
75	2019-06-24 22:14:47	name	"um teste de novo agente"
76	2019-06-24 22:14:47	publicLocation	true
77	2019-06-24 22:14:47	location	{"latitude":"0","longitude":"0"}
78	2019-06-24 22:14:47	shortDescription	"asd asd asd asd asd "
79	2019-06-24 22:14:47	longDescription	"asd asd asd asd a"
80	2019-06-24 22:14:47	createTimestamp	{"date":"2019-06-24 22:14:47.000000","timezone_type":3,"timezone":"UTC"}
81	2019-06-24 22:14:47	status	1
82	2019-06-24 22:14:47	updateTimestamp	null
83	2019-06-24 22:14:47	_subsiteId	null
84	2019-06-24 22:14:47	_terms	{"":["Audiovisual","Artes Visuais","Arte de Rua"]}
85	2019-06-24 22:36:05	name	"vamos ver (modificado asdasdasd)"
86	2019-06-24 22:36:05	publicLocation	false
217	2019-06-28 18:05:52	_ownerId	1
87	2019-06-24 22:36:05	updateTimestamp	{"date":"2019-06-24 22:36:05.000000","timezone_type":3,"timezone":"UTC"}
88	2019-06-24 22:48:06	publicLocation	false
89	2019-06-24 22:48:06	updateTimestamp	{"date":"2019-06-24 22:48:06.000000","timezone_type":3,"timezone":"UTC"}
90	2019-06-24 22:48:06	_terms	{"":["teste","LGBTs","agente","Audiovisual","Artes Visuais","Arte de Rua"]}
91	2019-06-24 23:00:46	updateTimestamp	{"date":"2019-06-24 23:00:46.000000","timezone_type":3,"timezone":"UTC"}
92	2019-06-24 23:00:46	_terms	{"":["testa","teste","LGBTs","agente","Audiovisual","Artes Visuais","Arte de Rua"]}
93	2019-06-24 23:00:57	updateTimestamp	{"date":"2019-06-24 23:00:57.000000","timezone_type":3,"timezone":"UTC"}
94	2019-06-24 23:00:57	_terms	{"":["testa","teste","LGBTs","agente","Audiovisual","Artes Visuais"]}
95	2019-06-24 23:02:15	updateTimestamp	{"date":"2019-06-24 23:02:15.000000","timezone_type":3,"timezone":"UTC"}
96	2019-06-24 23:02:15	emailPublico	"rafael@hacklab.com.br"
97	2019-06-24 23:02:15	site	"https:\\/\\/hacklab.com.br"
98	2019-06-24 23:02:15	telefonePublico	"(11) 96465-5828"
99	2019-06-24 23:02:49	updateTimestamp	{"date":"2019-06-24 23:02:49.000000","timezone_type":3,"timezone":"UTC"}
100	2019-06-24 23:02:49	instagram	"@rafachaves"
101	2019-06-25 14:25:45	name	"vamos ver (modificado asdasdasd ok)"
102	2019-06-25 14:25:45	updateTimestamp	{"date":"2019-06-25 14:25:45.000000","timezone_type":3,"timezone":"UTC"}
103	2019-06-25 14:55:09	updateTimestamp	{"date":"2019-06-25 14:55:09.000000","timezone_type":3,"timezone":"UTC"}
104	2019-06-25 14:55:09	parent	{"id":1,"name":"Admin@local","revision":1}
105	2019-06-25 20:39:07	publicLocation	false
106	2019-06-25 20:39:07	shortDescription	"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria"
107	2019-06-25 20:39:07	longDescription	""
108	2019-06-25 20:39:07	updateTimestamp	{"date":"2019-06-25 20:39:07.000000","timezone_type":3,"timezone":"UTC"}
109	2019-06-25 20:39:07	_terms	{"":["administrados","Economia Criativa","Cultura Estrangeira (imigrantes)","Cultura Cigana","Cinema"]}
110	2019-06-25 20:39:43	updateTimestamp	{"date":"2019-06-25 20:39:43.000000","timezone_type":3,"timezone":"UTC"}
111	2019-06-25 20:39:43	_terms	{"":["teste","Artesanato","Arte de Rua","Arqueologia","administrados","Economia Criativa","Cultura Estrangeira (imigrantes)","Cultura Cigana","Cinema"]}
112	2019-06-25 20:52:03	_type	2
113	2019-06-25 20:52:03	location	{"latitude":"0","longitude":"0"}
114	2019-06-25 20:52:03	updateTimestamp	{"date":"2019-06-25 20:52:03.000000","timezone_type":3,"timezone":"UTC"}
115	2019-06-25 20:52:44	updateTimestamp	{"date":"2019-06-25 20:52:44.000000","timezone_type":3,"timezone":"UTC"}
116	2019-06-25 20:53:43	_type	2
117	2019-06-25 20:53:43	updateTimestamp	{"date":"2019-06-25 20:53:43.000000","timezone_type":3,"timezone":"UTC"}
118	2019-06-25 20:53:43	parent	{"id":1,"name":"Admin@local","revision":42}
119	2019-06-25 21:04:56	_type	1
120	2019-06-25 21:04:56	name	"vamos ver (modificado asdasdasd ok - modificado)"
121	2019-06-25 21:04:56	updateTimestamp	{"date":"2019-06-25 21:04:56.000000","timezone_type":3,"timezone":"UTC"}
122	2019-06-25 21:05:07	_type	2
123	2019-06-25 21:05:07	name	"vamos ver (modificado asdasdasd ok)"
124	2019-06-25 21:05:07	updateTimestamp	{"date":"2019-06-25 21:05:07.000000","timezone_type":3,"timezone":"UTC"}
125	2019-06-25 21:47:54	_type	1
126	2019-06-25 21:47:54	name	"EVENTO DE TESTE"
127	2019-06-25 21:47:54	shortDescription	"dequena descri\\u00e7\\u00e3o"
128	2019-06-25 21:47:54	longDescription	"DESCRI\\u00c7\\u00c3O LONGA"
129	2019-06-25 21:47:54	rules	null
130	2019-06-25 21:47:54	createTimestamp	{"date":"2019-06-25 21:47:54.000000","timezone_type":3,"timezone":"UTC"}
131	2019-06-25 21:47:54	status	1
132	2019-06-25 21:47:54	updateTimestamp	null
133	2019-06-25 21:47:54	_subsiteId	null
134	2019-06-25 21:47:54	owner	{"id":1,"name":"Admin@local","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":42}
135	2019-06-25 21:47:54	classificacaoEtaria	"16 anos"
136	2019-06-25 21:47:54	subTitle	"subt\\u00edtulo"
137	2019-06-25 21:47:54	_terms	{"":["LGBTs","Cultura Ind\\u00edgena","Cinema"]}
138	2019-06-25 21:57:38	updateTimestamp	{"date":"2019-06-25 21:57:38.000000","timezone_type":3,"timezone":"UTC"}
139	2019-06-25 21:57:38	facebook	"https:\\/\\/facebook.com\\/teste"
140	2019-06-25 22:31:32	updateTimestamp	{"date":"2019-06-25 22:31:32.000000","timezone_type":3,"timezone":"UTC"}
141	2019-06-25 22:31:32	emailPublico	"rafael@hacklab.com.br"
142	2019-06-25 22:31:32	_events	[{"id":1,"name":"EVENTO DE TESTE","revision":47}]
143	2019-06-25 22:31:50	updateTimestamp	{"date":"2019-06-25 22:31:50.000000","timezone_type":3,"timezone":"UTC"}
144	2019-06-25 22:31:50	parent	{"id":1,"name":"Admin@local","revision":48}
145	2019-06-25 22:31:50	emailPublico	"rafael@hacklab.com.br"
146	2019-06-25 22:51:44	_type	1
147	2019-06-25 22:51:44	updateTimestamp	{"date":"2019-06-25 22:51:44.000000","timezone_type":3,"timezone":"UTC"}
148	2019-06-25 22:51:44	emailPublico	""
149	2019-06-25 22:51:56	name	"Administrador do Sistema"
150	2019-06-25 22:51:56	updateTimestamp	{"date":"2019-06-25 22:51:56.000000","timezone_type":3,"timezone":"UTC"}
151	2019-06-26 18:03:56	updateTimestamp	{"date":"2019-06-26 18:03:56.000000","timezone_type":3,"timezone":"UTC"}
152	2019-06-26 18:03:56	parent	{"id":1,"name":"Administrador do Sistema","revision":51}
153	2019-06-26 18:04:54	updateTimestamp	{"date":"2019-06-26 18:04:54.000000","timezone_type":3,"timezone":"UTC"}
154	2019-06-26 18:05:31	updateTimestamp	{"date":"2019-06-26 18:05:31.000000","timezone_type":3,"timezone":"UTC"}
155	2019-06-26 18:06:09	updateTimestamp	{"date":"2019-06-26 18:06:09.000000","timezone_type":3,"timezone":"UTC"}
156	2019-06-26 18:06:21	updateTimestamp	{"date":"2019-06-26 18:06:21.000000","timezone_type":3,"timezone":"UTC"}
157	2019-06-26 18:07:30	updateTimestamp	{"date":"2019-06-26 18:07:30.000000","timezone_type":3,"timezone":"UTC"}
158	2019-06-26 18:09:47	updateTimestamp	{"date":"2019-06-26 18:09:47.000000","timezone_type":3,"timezone":"UTC"}
159	2019-06-26 18:11:26	updateTimestamp	{"date":"2019-06-26 18:11:26.000000","timezone_type":3,"timezone":"UTC"}
160	2019-06-26 18:11:56	updateTimestamp	{"date":"2019-06-26 18:11:56.000000","timezone_type":3,"timezone":"UTC"}
161	2019-06-26 18:13:16	updateTimestamp	{"date":"2019-06-26 18:13:16.000000","timezone_type":3,"timezone":"UTC"}
162	2019-06-26 18:21:24	updateTimestamp	{"date":"2019-06-26 18:21:24.000000","timezone_type":3,"timezone":"UTC"}
163	2019-06-26 18:22:02	updateTimestamp	{"date":"2019-06-26 18:22:02.000000","timezone_type":3,"timezone":"UTC"}
164	2019-06-26 18:23:06	updateTimestamp	{"date":"2019-06-26 18:23:05.000000","timezone_type":3,"timezone":"UTC"}
165	2019-06-26 18:24:10	updateTimestamp	{"date":"2019-06-26 18:24:10.000000","timezone_type":3,"timezone":"UTC"}
166	2019-06-26 18:24:30	updateTimestamp	{"date":"2019-06-26 18:24:30.000000","timezone_type":3,"timezone":"UTC"}
167	2019-06-26 18:24:41	updateTimestamp	{"date":"2019-06-26 18:24:41.000000","timezone_type":3,"timezone":"UTC"}
168	2019-06-26 18:27:28	updateTimestamp	{"date":"2019-06-26 18:27:28.000000","timezone_type":3,"timezone":"UTC"}
169	2019-06-26 18:27:44	updateTimestamp	{"date":"2019-06-26 18:27:44.000000","timezone_type":3,"timezone":"UTC"}
170	2019-06-26 18:28:55	updateTimestamp	{"date":"2019-06-26 18:28:55.000000","timezone_type":3,"timezone":"UTC"}
171	2019-06-26 18:29:08	updateTimestamp	{"date":"2019-06-26 18:29:08.000000","timezone_type":3,"timezone":"UTC"}
172	2019-06-26 18:30:21	updateTimestamp	{"date":"2019-06-26 18:30:21.000000","timezone_type":3,"timezone":"UTC"}
173	2019-06-26 18:31:47	updateTimestamp	{"date":"2019-06-26 18:31:47.000000","timezone_type":3,"timezone":"UTC"}
174	2019-06-26 18:33:36	updateTimestamp	{"date":"2019-06-26 18:33:36.000000","timezone_type":3,"timezone":"UTC"}
175	2019-06-26 18:34:07	updateTimestamp	{"date":"2019-06-26 18:34:07.000000","timezone_type":3,"timezone":"UTC"}
176	2019-06-26 18:35:06	updateTimestamp	{"date":"2019-06-26 18:35:06.000000","timezone_type":3,"timezone":"UTC"}
177	2019-06-26 18:35:18	updateTimestamp	{"date":"2019-06-26 18:35:18.000000","timezone_type":3,"timezone":"UTC"}
178	2019-06-26 18:35:41	updateTimestamp	{"date":"2019-06-26 18:35:41.000000","timezone_type":3,"timezone":"UTC"}
179	2019-06-26 18:35:47	updateTimestamp	{"date":"2019-06-26 18:35:47.000000","timezone_type":3,"timezone":"UTC"}
180	2019-06-26 18:35:57	updateTimestamp	{"date":"2019-06-26 18:35:57.000000","timezone_type":3,"timezone":"UTC"}
181	2019-06-26 18:36:05	updateTimestamp	{"date":"2019-06-26 18:36:05.000000","timezone_type":3,"timezone":"UTC"}
182	2019-06-26 18:36:15	updateTimestamp	{"date":"2019-06-26 18:36:15.000000","timezone_type":3,"timezone":"UTC"}
183	2019-06-26 18:36:50	updateTimestamp	{"date":"2019-06-26 18:36:50.000000","timezone_type":3,"timezone":"UTC"}
184	2019-06-26 18:37:10	updateTimestamp	{"date":"2019-06-26 18:37:10.000000","timezone_type":3,"timezone":"UTC"}
185	2019-06-26 18:41:34	updateTimestamp	{"date":"2019-06-26 18:41:34.000000","timezone_type":3,"timezone":"UTC"}
186	2019-06-26 18:41:46	updateTimestamp	{"date":"2019-06-26 18:41:46.000000","timezone_type":3,"timezone":"UTC"}
187	2019-06-26 18:41:52	updateTimestamp	{"date":"2019-06-26 18:41:52.000000","timezone_type":3,"timezone":"UTC"}
188	2019-06-26 18:52:51	updateTimestamp	{"date":"2019-06-26 18:52:51.000000","timezone_type":3,"timezone":"UTC"}
189	2019-06-26 18:53:17	updateTimestamp	{"date":"2019-06-26 18:53:17.000000","timezone_type":3,"timezone":"UTC"}
190	2019-06-26 19:01:38	updateTimestamp	{"date":"2019-06-26 19:01:38.000000","timezone_type":3,"timezone":"UTC"}
191	2019-06-26 19:19:23	updateTimestamp	{"date":"2019-06-26 19:19:23.000000","timezone_type":3,"timezone":"UTC"}
192	2019-06-26 19:21:15	updateTimestamp	{"date":"2019-06-26 19:21:15.000000","timezone_type":3,"timezone":"UTC"}
193	2019-06-26 19:21:28	updateTimestamp	{"date":"2019-06-26 19:21:28.000000","timezone_type":3,"timezone":"UTC"}
194	2019-06-26 19:27:45	updateTimestamp	{"date":"2019-06-26 19:27:45.000000","timezone_type":3,"timezone":"UTC"}
195	2019-06-26 19:28:23	updateTimestamp	{"date":"2019-06-26 19:28:23.000000","timezone_type":3,"timezone":"UTC"}
196	2019-06-26 19:28:47	updateTimestamp	{"date":"2019-06-26 19:28:47.000000","timezone_type":3,"timezone":"UTC"}
197	2019-06-26 19:29:12	updateTimestamp	{"date":"2019-06-26 19:29:12.000000","timezone_type":3,"timezone":"UTC"}
198	2019-06-26 19:32:26	updateTimestamp	{"date":"2019-06-26 19:32:26.000000","timezone_type":3,"timezone":"UTC"}
199	2019-06-26 19:32:41	updateTimestamp	{"date":"2019-06-26 19:32:41.000000","timezone_type":3,"timezone":"UTC"}
200	2019-06-26 19:32:50	updateTimestamp	{"date":"2019-06-26 19:32:50.000000","timezone_type":3,"timezone":"UTC"}
201	2019-06-26 19:55:37	updateTimestamp	{"date":"2019-06-26 19:55:37.000000","timezone_type":3,"timezone":"UTC"}
202	2019-06-26 19:55:43	updateTimestamp	{"date":"2019-06-26 19:55:43.000000","timezone_type":3,"timezone":"UTC"}
203	2019-06-26 20:45:13	location	{"latitude":"-46.70643925","longitude":"-23.54109315"}
204	2019-06-26 20:45:13	updateTimestamp	{"date":"2019-06-26 20:45:13.000000","timezone_type":3,"timezone":"UTC"}
205	2019-06-26 20:45:40	location	{"latitude":"-23.54109315","longitude":"-46.70643925"}
206	2019-06-26 20:45:40	updateTimestamp	{"date":"2019-06-26 20:45:40.000000","timezone_type":3,"timezone":"UTC"}
207	2019-06-26 20:46:12	location	{"latitude":"-23.5466986","longitude":"-46.6469345087734"}
208	2019-06-26 20:46:12	updateTimestamp	{"date":"2019-06-26 20:46:12.000000","timezone_type":3,"timezone":"UTC"}
209	2019-06-28 18:05:52	location	{"latitude":"-23.53874975","longitude":"-46.6950873759422"}
210	2019-06-28 18:05:52	name	"Igreja Nossa Senhora da Mem\\u00f3ria RAM"
211	2019-06-28 18:05:52	public	false
212	2019-06-28 18:05:52	shortDescription	"Ordem do Ap\\u00f3stolo GPU"
213	2019-06-28 18:05:52	longDescription	"Mussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo! Pra l\\u00e1 , depois divoltis porris, paradis. Copo furadis \\u00e9 disculpa de bebadis, arcu quam euismod magna. Si num tem leite ent\\u00e3o bota uma pinga a\\u00ed cumpadi!\\r\\n\\r\\nSi u mundo t\\u00e1 muito paradis? Toma um m\\u00e9 que o mundo vai girarzis! Mais vale um bebadis conhecidiss, que um alcoolatra anonimis. Paisis, filhis, espiritis santis. Em p\\u00e9 sem cair, deitado sem dormir, sentado sem cochilar e fazendo pose.\\r\\n\\r\\nQuem num gosta di mim que vai ca\\u00e7\\u00e1 sua turmis! Viva Forevis aptent taciti sociosqu ad litora torquent. Nullam volutpat risus nec leo commodo, ut interdum diam laoreet. Sed non consequat odio. Detraxit consequat et quo num tendi nada."
214	2019-06-28 18:05:52	createTimestamp	{"date":"2019-06-28 18:05:52.000000","timezone_type":3,"timezone":"UTC"}
215	2019-06-28 18:05:52	status	1
216	2019-06-28 18:05:52	_type	84
218	2019-06-28 18:05:52	updateTimestamp	null
219	2019-06-28 18:05:52	_subsiteId	null
220	2019-06-28 18:05:52	owner	{"id":1,"name":"Administrador do Sistema","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
221	2019-06-28 18:05:52	_terms	{"":["mem\\u00f3ria ram","igueja","Artesanato","Arte Digital","Arquitetura-Urbanismo"]}
222	2019-06-28 18:06:06	shortDescription	"Ordem do Ap\\u00f3stolo GPU\\r\\nMussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo! "
223	2019-06-28 18:06:06	updateTimestamp	{"date":"2019-06-28 18:06:06.000000","timezone_type":3,"timezone":"UTC"}
224	2019-06-28 19:17:59	updateTimestamp	{"date":"2019-06-28 19:17:59.000000","timezone_type":3,"timezone":"UTC"}
225	2019-06-28 19:17:59	endereco	"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP"
226	2019-06-28 19:18:16	public	true
227	2019-06-28 19:18:16	updateTimestamp	{"date":"2019-06-28 19:18:16.000000","timezone_type":3,"timezone":"UTC"}
228	2019-07-02 21:07:34	_type	1
229	2019-07-02 21:07:34	name	"evento 1"
230	2019-07-02 21:07:34	shortDescription	"pequena descri\\u00e7\\u00e3o"
231	2019-07-02 21:07:34	longDescription	""
232	2019-07-02 21:07:34	rules	null
233	2019-07-02 21:07:34	createTimestamp	{"date":"2019-07-02 21:07:34.000000","timezone_type":3,"timezone":"UTC"}
234	2019-07-02 21:07:34	status	1
235	2019-07-02 21:07:34	updateTimestamp	null
236	2019-07-02 21:07:34	_subsiteId	null
237	2019-07-02 21:07:34	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
238	2019-07-02 21:07:34	classificacaoEtaria	"14 anos"
239	2019-07-02 21:07:35	_terms	{"":["Cinema"]}
240	2019-07-04 19:27:32	rules	""
241	2019-07-04 19:27:32	updateTimestamp	{"date":"2019-07-04 19:27:32.000000","timezone_type":3,"timezone":"UTC"}
242	2019-07-04 19:27:32	occurrences	{"1":{"items":[{"id":1,"description":"Toda seg, qua e sex de 1 de junho a 1 de agosto de 2019 \\u00e0s 12:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"1","startsAt":"12:00","duration":120,"endsAt":"14:00","frequency":"weekly","startsOn":"2019-06-01","until":"2019-08-01","day":{"1":"on","3":"on","5":"on"},"description":"Toda seg, qua e sex de 1 de junho a 1 de agosto de 2019 \\u00e0s 12:00","price":"gratuito"}}],"name":"Igreja Nossa Senhora da Mem\\u00f3ria RAM","location":{"latitude":"-23.53874975","longitude":"-46.6950873759422"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":109}}
243	2019-07-05 15:02:26	_type	1
244	2019-07-05 15:02:26	name	"teste1"
245	2019-07-05 15:02:26	shortDescription	"desc"
246	2019-07-05 15:02:26	longDescription	""
247	2019-07-05 15:02:26	rules	null
248	2019-07-05 15:02:26	createTimestamp	{"date":"2019-07-05 15:02:26.000000","timezone_type":3,"timezone":"UTC"}
249	2019-07-05 15:02:26	status	0
250	2019-07-05 15:02:26	updateTimestamp	null
251	2019-07-05 15:02:26	_subsiteId	null
252	2019-07-05 15:02:26	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
253	2019-07-05 15:02:26	classificacaoEtaria	"16 anos"
254	2019-07-05 15:02:26	_terms	{"":["Cinema"]}
255	2019-07-05 19:23:59	shortDescription	"Ordem do Ap\\u00f3stolo GPU\\nMussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo!"
256	2019-07-05 19:23:59	longDescription	"Mussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo! Pra l\\u00e1 , depois divoltis porris, paradis. Copo furadis \\u00e9 disculpa de bebadis, arcu quam euismod magna. Si num tem leite ent\\u00e3o bota uma pinga a\\u00ed cumpadi!\\n\\nSi u mundo t\\u00e1 muito paradis? Toma um m\\u00e9 que o mundo vai girarzis! Mais vale um bebadis conhecidiss, que um alcoolatra anonimis. Paisis, filhis, espiritis santis. Em p\\u00e9 sem cair, deitado sem dormir, sentado sem cochilar e fazendo pose.\\n\\nQuem num gosta di mim que vai ca\\u00e7\\u00e1 sua turmis! Viva Forevis aptent taciti sociosqu ad litora torquent. Nullam volutpat risus nec leo commodo, ut interdum diam laoreet. Sed non consequat odio. Detraxit consequat et quo num tendi nada."
257	2019-07-05 19:23:59	updateTimestamp	{"date":"2019-07-05 19:23:59.000000","timezone_type":3,"timezone":"UTC"}
258	2019-07-05 19:23:59	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
259	2019-07-05 19:23:59	_terms	{"":["RAFAEL","gpu","mem\\u00f3ria ram","igueja","Artesanato","Arte Digital","Arquitetura-Urbanismo"]}
260	2019-07-05 19:24:52	_type	102
261	2019-07-05 19:24:52	updateTimestamp	{"date":"2019-07-05 19:24:52.000000","timezone_type":3,"timezone":"UTC"}
262	2019-07-05 20:53:18	_type	1
263	2019-07-05 20:53:18	name	"Primeira tentativa de um evento do wp"
264	2019-07-05 20:53:18	shortDescription	"descri\\u00e7\\u00e3o curta"
265	2019-07-05 20:53:18	longDescription	"descri\\u00e7\\u00e3o loga "
266	2019-07-05 20:53:18	rules	null
267	2019-07-05 20:53:18	createTimestamp	{"date":"2019-07-05 20:53:18.000000","timezone_type":3,"timezone":"UTC"}
268	2019-07-05 20:53:18	status	1
269	2019-07-05 20:53:18	updateTimestamp	null
270	2019-07-05 20:53:18	_subsiteId	null
271	2019-07-05 20:53:18	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
272	2019-07-05 20:53:18	classificacaoEtaria	"12 anos"
273	2019-07-05 20:53:18	subTitle	"subt"
274	2019-07-05 20:53:18	_terms	{"":["taga","serve","qualquer","palavra","estudio","Cultura Tradicional","Cinema","Artes Integradas"]}
275	2019-07-05 21:03:56	updateTimestamp	{"date":"2019-07-05 21:03:56.000000","timezone_type":3,"timezone":"UTC"}
276	2019-07-05 21:20:29	location	{"latitude":"-23.53874975","longitude":"-46.6950873759422"}
277	2019-07-05 21:20:29	name	"Condom\\u00ednio Cultural"
278	2019-07-05 21:20:29	public	false
279	2019-07-05 21:20:29	shortDescription	"mundiamente conhecido como Cond\\u00f4"
280	2019-07-05 21:20:29	longDescription	""
281	2019-07-05 21:20:29	createTimestamp	{"date":"2019-07-05 21:20:29.000000","timezone_type":3,"timezone":"UTC"}
282	2019-07-05 21:20:29	status	1
283	2019-07-05 21:20:29	_type	41
284	2019-07-05 21:20:29	_ownerId	1
285	2019-07-05 21:20:29	updateTimestamp	null
286	2019-07-05 21:20:29	_subsiteId	null
287	2019-07-05 21:20:29	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
288	2019-07-05 21:20:29	criterios	"ser gente boa"
289	2019-07-05 21:20:29	emailPublico	"contato@condominiocultural.art.br"
290	2019-07-05 21:20:29	En_Bairro	"Perdizes"
291	2019-07-05 21:20:29	endereco	"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP"
292	2019-07-05 21:20:29	En_Estado	"SP"
293	2019-07-05 21:20:29	En_Municipio	"S\\u00e3o Paulo"
294	2019-07-05 21:20:29	En_Nome_Logradouro	"Rua Mundo Novo"
295	2019-07-05 21:20:29	En_Num	"342"
296	2019-07-05 21:20:29	_terms	{"":["cond\\u00f4","Teatro","Produ\\u00e7\\u00e3o Cultural","Outros","M\\u00fasica","Gest\\u00e3o Cultural","Economia Criativa","Dan\\u00e7a","Cultura Digital","Comunica\\u00e7\\u00e3o","Circo","Cinema","Audiovisual","Artes Visuais"]}
297	2019-07-05 21:20:35	updateTimestamp	{"date":"2019-07-05 21:20:35.000000","timezone_type":3,"timezone":"UTC"}
298	2019-07-05 21:32:32	location	{"latitude":"-23.5395286","longitude":"-46.6916363"}
299	2019-07-05 21:32:32	name	"hacklab\\/"
300	2019-07-05 21:32:32	public	false
301	2019-07-05 21:32:32	shortDescription	"o hacklab existe para existir"
302	2019-07-05 21:32:32	longDescription	"empresa"
303	2019-07-05 21:32:32	createTimestamp	{"date":"2019-07-05 21:32:32.000000","timezone_type":3,"timezone":"UTC"}
304	2019-07-05 21:32:32	status	1
305	2019-07-05 21:32:32	_type	106
306	2019-07-05 21:32:32	_ownerId	1
307	2019-07-05 21:32:32	updateTimestamp	null
308	2019-07-05 21:32:32	_subsiteId	null
309	2019-07-05 21:32:32	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
310	2019-07-05 21:32:32	capacidade	"25"
311	2019-07-05 21:32:32	emailPublico	"contato@hacklab.com.br"
312	2019-07-05 21:32:32	En_Bairro	"Perdizes"
313	2019-07-05 21:32:32	endereco	"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP"
314	2019-07-05 21:32:32	En_Estado	"SP"
315	2019-07-05 21:32:32	En_Municipio	"S\\u00e3o Paulo"
316	2019-07-05 21:32:32	En_Nome_Logradouro	"Rua Mundo Novo"
317	2019-07-05 21:32:32	En_Num	"342"
318	2019-07-05 21:32:32	facebook	"https:\\/\\/www.facebook.com\\/hacklabr\\/"
319	2019-07-05 21:32:32	site	"https:\\/\\/hacklab.com.br"
320	2019-07-05 21:32:32	_terms	{"":["Cultura Digital"]}
321	2019-07-05 21:32:44	updateTimestamp	{"date":"2019-07-05 21:32:44.000000","timezone_type":3,"timezone":"UTC"}
322	2019-07-05 21:33:16	updateTimestamp	{"date":"2019-07-05 21:33:16.000000","timezone_type":3,"timezone":"UTC"}
323	2019-07-05 21:34:30	_type	1
324	2019-07-05 21:34:30	name	"hackaton"
325	2019-07-05 21:34:30	shortDescription	"hackaton para desenvolvimento do novo site do hacklab\\/"
326	2019-07-05 21:34:30	longDescription	""
327	2019-07-05 21:34:30	rules	null
328	2019-07-05 21:34:30	createTimestamp	{"date":"2019-07-05 21:34:30.000000","timezone_type":3,"timezone":"UTC"}
329	2019-07-05 21:34:30	status	1
330	2019-07-05 21:34:30	updateTimestamp	null
331	2019-07-05 21:34:30	_subsiteId	null
332	2019-07-05 21:34:30	owner	{"id":1,"name":"Administrador do Sistema ok","shortDescription":"o resumo, ou descri\\u00e7\\u00e3o curta, \\u00e9 obrigat\\u00f3ria","revision":51}
333	2019-07-05 21:34:30	classificacaoEtaria	"16 anos"
334	2019-07-05 21:34:30	_terms	{"":["hackaton","Outros","Cultura Digital"]}
335	2019-07-05 21:35:58	updateTimestamp	{"date":"2019-07-05 21:35:58.000000","timezone_type":3,"timezone":"UTC"}
336	2019-07-05 21:35:58	occurrences	{"3":{"items":[{"id":3,"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"3","startsAt":"18:00","duration":240,"endsAt":"22:00","frequency":"weekly","startsOn":"2019-07-15","until":"2019-08-30","day":{"3":"on","5":"on"},"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","price":"R$100"}}],"name":"hacklab\\/","location":{"latitude":"-23.5395286","longitude":"-46.6916363"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":121}}
337	2019-07-05 21:39:03	updateTimestamp	{"date":"2019-07-05 21:39:03.000000","timezone_type":3,"timezone":"UTC"}
338	2019-07-05 21:39:03	occurrences	{"3":{"items":[{"id":3,"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"3","startsAt":"18:00","duration":240,"endsAt":"22:00","frequency":"weekly","startsOn":"2019-07-15","until":"2019-08-30","day":{"3":"on","5":"on"},"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","price":"R$100"}}],"name":"hacklab\\/","location":{"latitude":"-23.5395286","longitude":"-46.6916363"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":121},"2":{"items":[{"id":4,"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"2","startsAt":"10:00","duration":300,"endsAt":"15:00","frequency":"weekly","startsOn":"2019-05-01","until":"2019-10-31","day":{"1":"on","3":"on","5":"on"},"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","price":"R$ 10,00"}}],"name":"Condom\\u00ednio Cultural","location":{"latitude":"-23.53874975","longitude":"-46.6950873759422"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":118}}
339	2019-07-05 21:44:46	updateTimestamp	{"date":"2019-07-05 21:44:46.000000","timezone_type":3,"timezone":"UTC"}
340	2019-07-05 21:44:46	occurrences	{"2":{"items":[{"id":4,"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"2","startsAt":"10:00","duration":300,"endsAt":"15:00","frequency":"weekly","startsOn":"2019-05-01","until":"2019-10-31","day":{"1":"on","3":"on","5":"on"},"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","price":"R$ 10,00"}}],"name":"Condom\\u00ednio Cultural","location":{"latitude":"-23.53874975","longitude":"-46.6950873759422"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":118},"3":{"items":[{"id":3,"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"3","startsAt":"18:00","duration":240,"endsAt":"22:00","frequency":"weekly","startsOn":"2019-07-12","until":"2019-08-30","day":{"3":"on","5":"on"},"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","price":"R$100"}}],"name":"hacklab\\/","location":{"latitude":"-23.5395286","longitude":"-46.6916363"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":121}}
341	2019-07-10 17:37:00	location	{"latitude":"35.6777691","longitude":"139.7646365"}
342	2019-07-10 17:37:00	updateTimestamp	{"date":"2019-07-10 17:37:00.000000","timezone_type":3,"timezone":"UTC"}
343	2019-07-10 22:22:12	location	{"latitude":"-23.5473702069444","longitude":"-46.665355686564"}
344	2019-07-10 22:22:12	updateTimestamp	{"date":"2019-07-10 22:22:12.000000","timezone_type":3,"timezone":"UTC"}
345	2019-07-13 21:08:58	updateTimestamp	{"date":"2019-07-13 21:08:58.000000","timezone_type":3,"timezone":"UTC"}
346	2019-07-13 21:08:58	_terms	{"":["Televis\\u00e3o"]}
347	2019-07-13 21:23:15	shortDescription	"Ordem do Ap\\u00f3stolo GPU\\r\\nMussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo!"
348	2019-07-13 21:23:15	longDescription	"Mussum Ipsum, cacilds vidis litro abertis. Todo mundo v\\u00ea os porris que eu tomo, mas ningu\\u00e9m v\\u00ea os tombis que eu levo! Pra l\\u00e1 , depois divoltis porris, paradis. Copo furadis \\u00e9 disculpa de bebadis, arcu quam euismod magna. Si num tem leite ent\\u00e3o bota uma pinga a\\u00ed cumpadi!\\r\\n\\r\\nSi u mundo t\\u00e1 muito paradis? Toma um m\\u00e9 que o mundo vai girarzis! Mais vale um bebadis conhecidiss, que um alcoolatra anonimis. Paisis, filhis, espiritis santis. Em p\\u00e9 sem cair, deitado sem dormir, sentado sem cochilar e fazendo pose.\\r\\n\\r\\nQuem num gosta di mim que vai ca\\u00e7\\u00e1 sua turmis! Viva Forevis aptent taciti sociosqu ad litora torquent. Nullam volutpat risus nec leo commodo, ut interdum diam laoreet. Sed non consequat odio. Detraxit consequat et quo num tendi nada."
349	2019-07-13 21:23:15	_type	84
350	2019-07-13 21:23:15	updateTimestamp	{"date":"2019-07-13 21:23:15.000000","timezone_type":3,"timezone":"UTC"}
351	2019-07-16 00:41:32	_type	1
352	2019-07-16 00:41:32	name	"teste"
353	2019-07-16 00:41:32	publicLocation	false
354	2019-07-16 00:41:32	location	{"latitude":"0","longitude":"0"}
355	2019-07-16 00:41:32	shortDescription	"asd asd asd a"
356	2019-07-16 00:41:32	longDescription	""
357	2019-07-16 00:41:32	createTimestamp	{"date":"2019-07-16 00:41:32.000000","timezone_type":3,"timezone":"UTC"}
358	2019-07-16 00:41:32	status	1
359	2019-07-16 00:41:32	updateTimestamp	null
360	2019-07-16 00:41:32	_subsiteId	null
361	2019-07-16 00:41:32	parent	{"id":1,"name":"Administrador do Sistema ok","revision":51}
362	2019-07-16 00:41:32	_terms	{"":["Arquitetura-Urbanismo"]}
363	2019-07-16 13:34:51	updateTimestamp	{"date":"2019-07-16 13:34:51.000000","timezone_type":3,"timezone":"UTC"}
364	2019-07-16 13:34:51	occurrences	{"2":{"items":[{"id":4,"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"2","startsAt":"10:00","duration":300,"endsAt":"15:00","frequency":"weekly","startsOn":"2019-05-01","until":"2019-10-31","day":{"1":"on","3":"on","5":"on"},"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","price":"R$ 10,00"}},{"id":5,"description":"Todo dom, seg, sex e s\\u00e1b de 1 de julho a 23 de agosto de 2019 \\u00e0s 12:33","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"2","startsAt":"12:33","duration":332,"endsAt":"18:05","frequency":"weekly","startsOn":"2019-07-01","until":"2019-08-23","day":{"0":"on","1":"on","5":"on","6":"on"},"description":"Todo dom, seg, sex e s\\u00e1b de 1 de julho a 23 de agosto de 2019 \\u00e0s 12:33","price":"gratuito"}}],"name":"Condom\\u00ednio Cultural","location":{"latitude":"-23.5473702069444","longitude":"-46.665355686564"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":127},"3":{"items":[{"id":3,"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","_startsOn":null,"_endsOn":null,"_startsAt":null,"_endsAt":null,"frequency":"weekly","count":null,"_until":null,"rule":{"spaceId":"3","startsAt":"18:00","duration":240,"endsAt":"22:00","frequency":"weekly","startsOn":"2019-07-12","until":"2019-08-30","day":{"3":"on","5":"on"},"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","price":"R$100"}}],"name":"hacklab\\/","location":{"latitude":"35.6777691","longitude":"139.7646365"},"endereco":"Rua Mundo Novo 342, Perdizes, S\\u00e3o Paulo, SP","revision":128}}
\.


--
-- Data for Name: entity_revision_revision_data; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.entity_revision_revision_data (revision_id, revision_data_id) FROM stdin;
1	1
1	2
1	3
1	4
1	5
1	6
1	7
1	8
1	9
1	10
2	11
2	12
2	13
2	14
2	15
2	16
2	17
2	18
2	19
2	20
2	21
3	11
3	12
3	13
3	14
3	15
3	16
3	17
3	18
3	22
3	20
3	23
3	21
4	11
4	12
4	13
4	14
4	15
4	24
4	17
4	18
4	25
4	20
4	23
4	21
5	11
5	12
5	13
5	14
5	15
5	24
5	17
5	18
5	26
5	20
5	23
5	21
6	11
6	12
6	13
6	14
6	15
6	24
6	17
6	18
6	27
6	20
6	28
6	21
7	11
7	12
7	13
7	14
7	15
7	24
7	17
7	18
7	29
7	20
7	28
7	21
8	11
8	12
8	13
8	14
8	15
8	24
8	17
8	18
8	30
8	20
8	28
8	21
9	11
9	12
9	31
9	14
9	15
9	24
9	17
9	18
9	32
9	20
9	28
9	21
10	11
10	12
10	31
10	14
10	15
10	24
10	17
10	18
10	33
10	20
10	28
10	21
11	11
11	12
11	31
11	14
11	15
11	24
11	17
11	18
11	34
11	20
11	28
11	21
12	11
12	12
12	35
12	14
12	15
12	24
12	17
12	18
12	36
12	20
12	28
12	21
13	11
13	12
13	35
13	14
13	15
13	24
13	17
13	18
13	37
13	20
13	28
13	21
14	11
14	12
14	38
14	14
14	15
14	24
14	17
14	18
14	39
14	20
14	28
14	21
15	11
15	12
15	40
15	14
15	15
15	24
15	17
15	18
15	41
15	20
15	28
15	21
16	11
16	12
16	42
16	14
16	15
16	24
16	17
16	18
16	43
16	20
16	28
16	21
17	11
17	12
17	42
17	14
17	15
17	24
17	17
17	18
17	44
17	20
17	45
17	28
17	21
18	11
18	12
18	42
18	14
18	15
18	24
18	17
18	18
18	46
18	20
18	47
18	28
18	21
19	11
19	12
19	42
19	14
19	15
19	24
19	17
19	18
19	48
19	20
19	49
19	28
19	21
20	11
20	12
20	42
20	14
20	15
20	24
20	17
20	18
20	48
20	20
20	50
20	28
20	21
21	11
21	12
21	42
21	14
21	15
21	24
21	17
21	18
21	51
21	20
21	50
21	28
21	21
22	11
22	12
22	42
22	14
22	15
22	24
22	17
22	18
22	52
22	20
22	50
22	28
22	21
23	11
23	12
23	42
23	14
23	15
23	24
23	17
23	18
23	53
23	20
23	50
23	28
23	21
24	54
24	55
24	56
24	57
24	58
24	59
24	60
24	61
24	62
24	63
24	64
25	54
25	65
25	56
25	57
25	58
25	59
25	60
25	61
25	66
25	63
25	64
26	11
26	12
26	42
26	14
26	15
26	24
26	17
26	18
26	67
26	20
26	50
26	28
26	21
27	11
27	12
27	42
27	14
27	15
27	24
27	17
27	18
27	68
27	20
27	50
27	28
27	21
28	54
28	69
28	70
28	57
28	58
28	59
28	60
28	61
28	71
28	63
28	64
29	54
29	72
29	73
29	57
29	58
29	59
29	60
29	61
29	71
29	63
29	64
30	74
30	75
30	76
30	77
30	78
30	79
30	80
30	81
30	82
30	83
30	84
31	54
31	85
31	86
31	57
31	58
31	59
31	60
31	61
31	87
31	63
31	64
32	74
32	75
32	88
32	77
32	78
32	79
32	80
32	81
32	89
32	83
32	90
33	74
33	75
33	88
33	77
33	78
33	79
33	80
33	81
33	91
33	83
33	92
34	74
34	75
34	88
34	77
34	78
34	79
34	80
34	81
34	93
34	83
34	94
35	74
35	75
35	88
35	77
35	78
35	79
35	80
35	81
35	95
35	83
35	96
35	97
35	98
35	94
36	74
36	75
36	88
36	77
36	78
36	79
36	80
36	81
36	99
36	83
36	96
36	100
36	97
36	98
36	94
37	54
37	101
37	86
37	57
37	58
37	59
37	60
37	61
37	102
37	63
37	64
38	11
38	12
38	42
38	14
38	15
38	24
38	17
38	18
38	103
38	20
38	104
38	50
38	28
38	21
39	1
39	2
39	105
39	4
39	106
39	107
39	7
39	8
39	108
39	10
39	109
40	1
40	2
40	105
40	4
40	106
40	107
40	7
40	8
40	110
40	10
40	111
41	112
41	2
41	105
41	113
41	106
41	107
41	7
41	8
41	114
41	10
41	111
42	112
42	2
42	105
42	113
42	106
42	107
42	7
42	8
42	115
42	10
42	111
43	116
43	101
43	86
43	57
43	58
43	59
43	60
43	61
43	117
43	63
43	118
43	64
44	119
44	120
44	86
44	57
44	58
44	59
44	60
44	61
44	121
44	63
44	118
44	64
45	122
45	123
45	86
45	57
45	58
45	59
45	60
45	61
45	124
45	63
45	118
45	64
46	125
46	126
46	127
46	128
46	129
46	130
46	131
46	132
46	133
46	134
46	135
46	136
46	137
47	125
47	126
47	127
47	128
47	129
47	130
47	131
47	138
47	133
47	134
47	135
47	139
47	136
47	137
48	112
48	2
48	105
48	113
48	106
48	107
48	7
48	8
48	140
48	10
48	141
48	142
48	111
49	11
49	12
49	42
49	14
49	15
49	24
49	17
49	18
49	143
49	20
49	144
49	145
49	50
49	28
49	21
50	146
50	2
50	105
50	113
50	106
50	107
50	7
50	8
50	147
50	10
50	148
50	142
50	111
51	146
51	149
51	105
51	113
51	106
51	107
51	7
51	8
51	150
51	10
51	148
51	142
51	111
52	11
52	12
52	42
52	14
52	15
52	24
52	17
52	18
52	151
52	20
52	152
52	145
52	50
52	28
52	21
53	11
53	12
53	42
53	14
53	15
53	24
53	17
53	18
53	153
53	20
53	152
53	145
53	50
53	28
53	21
54	11
54	12
54	42
54	14
54	15
54	24
54	17
54	18
54	154
54	20
54	152
54	145
54	50
54	28
54	21
55	11
55	12
55	42
55	14
55	15
55	24
55	17
55	18
55	155
55	20
55	152
55	145
55	50
55	28
55	21
56	11
56	12
56	42
56	14
56	15
56	24
56	17
56	18
56	156
56	20
56	152
56	145
56	50
56	28
56	21
57	11
57	12
57	42
57	14
57	15
57	24
57	17
57	18
57	157
57	20
57	152
57	145
57	50
57	28
57	21
58	11
58	12
58	42
58	14
58	15
58	24
58	17
58	18
58	158
58	20
58	152
58	145
58	50
58	28
58	21
59	11
59	12
59	42
59	14
59	15
59	24
59	17
59	18
59	159
59	20
59	152
59	145
59	50
59	28
59	21
60	11
60	12
60	42
60	14
60	15
60	24
60	17
60	18
60	160
60	20
60	152
60	145
60	50
60	28
60	21
61	11
61	12
61	42
61	14
61	15
61	24
61	17
61	18
61	161
61	20
61	152
61	145
61	50
61	28
61	21
62	11
62	12
62	42
62	14
62	15
62	24
62	17
62	18
62	162
62	20
62	152
62	145
62	50
62	28
62	21
63	11
63	12
63	42
63	14
63	15
63	24
63	17
63	18
63	163
63	20
63	152
63	145
63	50
63	28
63	21
64	11
64	12
64	42
64	14
64	15
64	24
64	17
64	18
64	164
64	20
64	152
64	145
64	50
64	28
64	21
65	11
65	12
65	42
65	14
65	15
65	24
65	17
65	18
65	165
65	20
65	152
65	145
65	50
65	28
65	21
66	11
66	12
66	42
66	14
66	15
66	24
66	17
66	18
66	166
66	20
66	152
66	145
66	50
66	28
66	21
67	11
67	12
67	42
67	14
67	15
67	24
67	17
67	18
67	167
67	20
67	152
67	145
67	50
67	28
67	21
68	11
68	12
68	42
68	14
68	15
68	24
68	17
68	18
68	168
68	20
68	152
68	145
68	50
68	28
68	21
69	11
69	12
69	42
69	14
69	15
69	24
69	17
69	18
69	169
69	20
69	152
69	145
69	50
69	28
69	21
70	11
70	12
70	42
70	14
70	15
70	24
70	17
70	18
70	170
70	20
70	152
70	145
70	50
70	28
70	21
71	11
71	12
71	42
71	14
71	15
71	24
71	17
71	18
71	171
71	20
71	152
71	145
71	50
71	28
71	21
72	11
72	12
72	42
72	14
72	15
72	24
72	17
72	18
72	172
72	20
72	152
72	145
72	50
72	28
72	21
73	11
73	12
73	42
73	14
73	15
73	24
73	17
73	18
73	173
73	20
73	152
73	145
73	50
73	28
73	21
74	11
74	12
74	42
74	14
74	15
74	24
74	17
74	18
74	174
74	20
74	152
74	145
74	50
74	28
74	21
75	11
75	12
75	42
75	14
75	15
75	24
75	17
75	18
75	175
75	20
75	152
75	145
75	50
75	28
75	21
76	11
76	12
76	42
76	14
76	15
76	24
76	17
76	18
76	176
76	20
76	152
76	145
76	50
76	28
76	21
77	11
77	12
77	42
77	14
77	15
77	24
77	17
77	18
77	177
77	20
77	152
77	145
77	50
77	28
77	21
78	11
78	12
78	42
78	14
78	15
78	24
78	17
78	18
78	178
78	20
78	152
78	145
78	50
78	28
78	21
79	11
79	12
79	42
79	14
79	15
79	24
79	17
79	18
79	179
79	20
79	152
79	145
79	50
79	28
79	21
80	11
80	12
80	42
80	14
80	15
80	24
80	17
80	18
80	180
80	20
80	152
80	145
80	50
80	28
80	21
81	11
81	12
81	42
81	14
81	15
81	24
81	17
81	18
81	181
81	20
81	152
81	145
81	50
81	28
81	21
82	11
82	12
82	42
82	14
82	15
82	24
82	17
82	18
82	182
82	20
82	152
82	145
82	50
82	28
82	21
83	11
83	12
83	42
83	14
83	15
83	24
83	17
83	18
83	183
83	20
83	152
83	145
83	50
83	28
83	21
84	11
84	12
84	42
84	14
84	15
84	24
84	17
84	18
84	184
84	20
84	152
84	145
84	50
84	28
84	21
85	11
85	12
85	42
85	14
85	15
85	24
85	17
85	18
85	185
85	20
85	152
85	145
85	50
85	28
85	21
86	11
86	12
86	42
86	14
86	15
86	24
86	17
86	18
86	186
86	20
86	152
86	145
86	50
86	28
86	21
87	11
87	12
87	42
87	14
87	15
87	24
87	17
87	18
87	187
87	20
87	152
87	145
87	50
87	28
87	21
88	11
88	12
88	42
88	14
88	15
88	24
88	17
88	18
88	188
88	20
88	152
88	145
88	50
88	28
88	21
89	11
89	12
89	42
89	14
89	15
89	24
89	17
89	18
89	189
89	20
89	152
89	145
89	50
89	28
89	21
90	11
90	12
90	42
90	14
90	15
90	24
90	17
90	18
90	190
90	20
90	152
90	145
90	50
90	28
90	21
91	11
91	12
91	42
91	14
91	15
91	24
91	17
91	18
91	191
91	20
91	152
91	145
91	50
91	28
91	21
92	11
92	12
92	42
92	14
92	15
92	24
92	17
92	18
92	192
92	20
92	152
92	145
92	50
92	28
92	21
93	11
93	12
93	42
93	14
93	15
93	24
93	17
93	18
93	193
93	20
93	152
93	145
93	50
93	28
93	21
94	11
94	12
94	42
94	14
94	15
94	24
94	17
94	18
94	194
94	20
94	152
94	145
94	50
94	28
94	21
95	11
95	12
95	42
95	14
95	15
95	24
95	17
95	18
95	195
95	20
95	152
95	145
95	50
95	28
95	21
96	11
96	12
96	42
96	14
96	15
96	24
96	17
96	18
96	196
96	20
96	152
96	145
96	50
96	28
96	21
97	11
97	12
97	42
97	14
97	15
97	24
97	17
97	18
97	197
97	20
97	152
97	145
97	50
97	28
97	21
98	11
98	12
98	42
98	14
98	15
98	24
98	17
98	18
98	198
98	20
98	152
98	145
98	50
98	28
98	21
99	11
99	12
99	42
99	14
99	15
99	24
99	17
99	18
99	199
99	20
99	152
99	145
99	50
99	28
99	21
100	11
100	12
100	42
100	14
100	15
100	24
100	17
100	18
100	200
100	20
100	152
100	145
100	50
100	28
100	21
101	11
101	12
101	42
101	14
101	15
101	24
101	17
101	18
101	201
101	20
101	152
101	145
101	50
101	28
101	21
102	11
102	12
102	42
102	14
102	15
102	24
102	17
102	18
102	202
102	20
102	152
102	145
102	50
102	28
102	21
103	11
103	12
103	42
103	203
103	15
103	24
103	17
103	18
103	204
103	20
103	152
103	145
103	50
103	28
103	21
104	11
104	12
104	42
104	205
104	15
104	24
104	17
104	18
104	206
104	20
104	152
104	145
104	50
104	28
104	21
105	11
105	12
105	42
105	207
105	15
105	24
105	17
105	18
105	208
105	20
105	152
105	145
105	50
105	28
105	21
106	209
106	210
106	211
106	212
106	213
106	214
106	215
106	216
106	217
106	218
106	219
106	220
106	221
107	209
107	210
107	211
107	222
107	213
107	214
107	215
107	216
107	217
107	223
107	219
107	220
107	221
108	209
108	210
108	211
108	222
108	213
108	214
108	215
108	216
108	217
108	224
108	219
108	220
108	225
108	221
109	209
109	210
109	226
109	222
109	213
109	214
109	215
109	216
109	217
109	227
109	219
109	220
109	225
109	221
110	228
110	229
110	230
110	231
110	232
110	233
110	234
110	235
110	236
110	237
110	238
110	239
111	228
111	229
111	230
111	231
111	240
111	233
111	234
111	241
111	236
111	237
111	238
111	239
111	242
112	243
112	244
112	245
112	246
112	247
112	248
112	249
112	250
112	251
112	252
112	253
112	254
113	209
113	210
113	226
113	255
113	256
113	214
113	215
113	216
113	217
113	257
113	219
113	258
113	225
113	259
114	209
114	210
114	226
114	255
114	256
114	214
114	215
114	260
114	217
114	261
114	219
114	258
114	225
114	259
115	262
115	263
115	264
115	265
115	266
115	267
115	268
115	269
115	270
115	271
115	272
115	273
115	274
116	262
116	263
116	264
116	265
116	266
116	267
116	268
116	275
116	270
116	271
116	272
116	273
116	274
117	276
117	277
117	278
117	279
117	280
117	281
117	282
117	283
117	284
117	285
117	286
117	287
117	288
117	289
117	290
117	291
117	292
117	293
117	294
117	295
117	296
118	276
118	277
118	278
118	279
118	280
118	281
118	282
118	283
118	284
118	297
118	286
118	287
118	288
118	289
118	290
118	291
118	292
118	293
118	294
118	295
118	296
119	298
119	299
119	300
119	301
119	302
119	303
119	304
119	305
119	306
119	307
119	308
119	309
119	310
119	311
119	312
119	313
119	314
119	315
119	316
119	317
119	318
119	319
119	320
120	298
120	299
120	300
120	301
120	302
120	303
120	304
120	305
120	306
120	321
120	308
120	309
120	310
120	311
120	312
120	313
120	314
120	315
120	316
120	317
120	318
120	319
120	320
121	298
121	299
121	300
121	301
121	302
121	303
121	304
121	305
121	306
121	322
121	308
121	309
121	310
121	311
121	312
121	313
121	314
121	315
121	316
121	317
121	318
121	319
121	320
122	323
122	324
122	325
122	326
122	327
122	328
122	329
122	330
122	331
122	332
122	333
122	334
123	323
123	324
123	325
123	326
123	327
123	328
123	329
123	335
123	331
123	332
123	333
123	334
123	336
124	323
124	324
124	325
124	326
124	327
124	328
124	329
124	337
124	331
124	332
124	333
124	334
124	338
125	323
125	324
125	325
125	326
125	327
125	328
125	329
125	339
125	331
125	332
125	333
125	334
125	340
126	341
126	299
126	300
126	301
126	302
126	303
126	304
126	305
126	306
126	342
126	308
126	309
126	310
126	311
126	312
126	313
126	314
126	315
126	316
126	317
126	318
126	319
126	320
127	343
127	277
127	278
127	279
127	280
127	281
127	282
127	283
127	284
127	344
127	286
127	287
127	288
127	289
127	290
127	291
127	292
127	293
127	294
127	295
127	296
128	341
128	299
128	300
128	301
128	302
128	303
128	304
128	305
128	306
128	345
128	308
128	309
128	310
128	311
128	312
128	313
128	314
128	315
128	316
128	317
128	318
128	319
128	346
129	209
129	210
129	226
129	347
129	348
129	214
129	215
129	349
129	217
129	350
129	219
129	258
129	225
129	259
130	351
130	352
130	353
130	354
130	355
130	356
130	357
130	358
130	359
130	360
130	361
130	362
131	323
131	324
131	325
131	326
131	327
131	328
131	329
131	363
131	331
131	332
131	333
131	334
131	364
\.


--
-- Data for Name: evaluation_method_configuration; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.evaluation_method_configuration (id, opportunity_id, type) FROM stdin;
\.


--
-- Data for Name: evaluationmethodconfiguration_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.evaluationmethodconfiguration_meta (id, object_id, key, value) FROM stdin;
\.


--
-- Data for Name: event; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.event (id, project_id, name, short_description, long_description, rules, create_timestamp, status, agent_id, is_verified, type, update_timestamp, subsite_id) FROM stdin;
5	\N	Primeira tentativa de um evento do wp	descrição curta	descrição loga 	\N	2019-07-05 20:53:18	1	1	f	1	2019-07-05 21:03:56	\N
3	\N	evento de teste	desc		\N	2019-07-05 15:02:26	1	1	f	1	2019-07-13 22:13:22	\N
6	\N	hackaton	hackaton para desenvolvimento do novo site do hacklab/		\N	2019-07-05 21:34:30	1	1	f	1	2019-07-16 13:34:51	\N
\.


--
-- Data for Name: event_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.event_meta (key, object_id, value, id) FROM stdin;
subTitle	5	subt	6
classificacaoEtaria	5	12 anos	7
subTitle	3	subte	8
classificacaoEtaria	6	16 anos	9
classificacaoEtaria	3	12 anos	5
\.


--
-- Data for Name: event_occurrence; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.event_occurrence (id, space_id, event_id, rule, starts_on, ends_on, starts_at, ends_at, frequency, separation, count, until, timezone_name, status) FROM stdin;
2	1	3	{"spaceId":"1","startsAt":"12:00","duration":123,"endsAt":"14:03","frequency":"daily","startsOn":"2019-07-11","until":"2019-07-31","description":"Diariamente de 11 a 31 de julho de 2019 \\u00e0s 12:00","price":"gradis"}	2019-07-11	\N	2019-07-11 12:00:00	2019-07-11 14:03:00	daily	1	\N	2019-07-31	Etc/UTC	1
4	2	6	{"spaceId":"2","startsAt":"10:00","duration":300,"endsAt":"15:00","frequency":"weekly","startsOn":"2019-05-01","until":"2019-10-31","day":{"1":"on","3":"on","5":"on"},"description":"Toda seg, qua e sex de 1 de maio a 31 de outubro de 2019 \\u00e0s 10:00","price":"R$ 10,00"}	2019-05-01	\N	2019-05-01 10:00:00	2019-05-01 15:00:00	weekly	1	\N	2019-10-31	Etc/UTC	1
3	3	6	{"spaceId":"3","startsAt":"18:00","duration":240,"endsAt":"22:00","frequency":"weekly","startsOn":"2019-07-12","until":"2019-08-30","day":{"3":"on","5":"on"},"description":"Toda qua e sex de 15 de julho a 30 de agosto de 2019 \\u00e0s 18:00","price":"R$100"}	2019-07-12	\N	2019-07-12 18:00:00	2019-07-12 22:00:00	weekly	1	\N	2019-08-30	Etc/UTC	1
5	2	6	{"spaceId":"2","startsAt":"12:33","duration":332,"endsAt":"18:05","frequency":"weekly","startsOn":"2019-07-01","until":"2019-08-23","day":{"0":"on","1":"on","5":"on","6":"on"},"description":"Todo dom, seg, sex e s\\u00e1b de 1 de julho a 23 de agosto de 2019 \\u00e0s 12:33","price":"gratuito"}	2019-07-01	\N	2019-07-01 12:33:00	2019-07-01 18:05:00	weekly	1	\N	2019-08-23	Etc/UTC	1
\.


--
-- Data for Name: event_occurrence_cancellation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.event_occurrence_cancellation (id, event_occurrence_id, date) FROM stdin;
\.


--
-- Data for Name: event_occurrence_recurrence; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.event_occurrence_recurrence (id, event_occurrence_id, month, day, week) FROM stdin;
6	4	\N	1	\N
7	4	\N	3	\N
8	4	\N	5	\N
9	3	\N	3	\N
10	3	\N	5	\N
11	5	\N	0	\N
12	5	\N	1	\N
13	5	\N	5	\N
14	5	\N	6	\N
\.


--
-- Data for Name: file; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.file (id, md5, mime_type, name, object_type, object_id, create_timestamp, grp, description, parent_id, path, private) FROM stdin;
75	b1b62e3800a257271d53d96cc7e32f09	image/jpeg	condo-044b689c83cb5834ab83f3f29399977a.jpg	MapasCulturais\\Entities\\Space	2	2019-07-05 21:20:35	img:avatarSmall	\N	74	space/2/file/74/condo-044b689c83cb5834ab83f3f29399977a.jpg	f
76	9ef9916bb7bbc5bf854bc5a9e1e2f13c	image/jpeg	condo-f036a0bf8b34a229a331147c38f06e64.jpg	MapasCulturais\\Entities\\Space	2	2019-07-05 21:20:35	img:avatarMedium	\N	74	space/2/file/74/condo-f036a0bf8b34a229a331147c38f06e64.jpg	f
77	2155a8eb1b43ad7ce4fb937dee6142f9	image/jpeg	condo-72a47504eaa9b972a5513d4d00c25d38.jpg	MapasCulturais\\Entities\\Space	2	2019-07-05 21:20:35	img:avatarBig	\N	74	space/2/file/74/condo-72a47504eaa9b972a5513d4d00c25d38.jpg	f
74	69c45cc244edcceb95538d5124c7fa90	image/jpeg	condo.jpg	MapasCulturais\\Entities\\Space	2	2019-07-05 21:20:35	avatar	\N	\N	space/2/condo.jpg	f
79	cc9f3774618dfdff85030935e22018c9	image/png	hacklab-734b390e1082fb95885167b0dc3222a1.png	MapasCulturais\\Entities\\Space	3	2019-07-05 21:32:45	img:avatarSmall	\N	78	space/3/file/78/hacklab-734b390e1082fb95885167b0dc3222a1.png	f
8	9cce30d208d3a1d1ba19951cc6a1a87c	image/jpeg	convite_aniversário_rudá_-_nascente-7eae93928c1266d52464c71eeeba892f.jpg	MapasCulturais\\Entities\\Agent	5	2019-06-25 14:59:29	img:galleryThumb	\N	7	agent/5/file/7/convite_aniversário_rudá_-_nascente-7eae93928c1266d52464c71eeeba892f.jpg	f
9	525b4743bd853690e71e365c29ae51ea	image/jpeg	convite_aniversário_rudá_-_nascente-b39b5a58f89e5ed452c40de2a865b54b.jpg	MapasCulturais\\Entities\\Agent	5	2019-06-25 14:59:29	img:galleryFull	\N	7	agent/5/file/7/convite_aniversário_rudá_-_nascente-b39b5a58f89e5ed452c40de2a865b54b.jpg	f
7	a3de47c300aa5f9769937ea1ab61b428	image/jpeg	convite_aniversário_rudá_-_nascente.jpg	MapasCulturais\\Entities\\Agent	5	2019-06-25 14:59:29	gallery	convite aniversário do rudá	\N	agent/5/convite_aniversário_rudá_-_nascente.jpg	f
80	fdf6af7119eb4a5988770a2e79e11c23	image/png	hacklab-4cc2bc48d2e0d158505190d68f748bfc.png	MapasCulturais\\Entities\\Space	3	2019-07-05 21:32:45	img:avatarMedium	\N	78	space/3/file/78/hacklab-4cc2bc48d2e0d158505190d68f748bfc.png	f
81	3e0b55f123a0a13b606d592f3f691388	image/png	hacklab-cc9d75b9134d8c589a4dd82f92a461cc.png	MapasCulturais\\Entities\\Space	3	2019-07-05 21:32:45	img:avatarBig	\N	78	space/3/file/78/hacklab-cc9d75b9134d8c589a4dd82f92a461cc.png	f
78	68f090a95c24689bc617605a5445fbd3	image/png	hacklab.png	MapasCulturais\\Entities\\Space	3	2019-07-05 21:32:45	avatar	\N	\N	space/3/hacklab.png	f
83	ba0fcbc8db6cc0b41ff41e63ca704745	image/png	blob-488f4cc193558be411f7ebf9c2292e80.png	MapasCulturais\\Entities\\Space	2	2019-07-10 21:23:00	img:header	\N	82	space/2/file/82/blob-488f4cc193558be411f7ebf9c2292e80.png	f
82	48dc9a2519cdac1d793cdcff652d0ff9	image/png	blob.png	MapasCulturais\\Entities\\Space	2	2019-07-10 21:23:00	header	\N	\N	space/2/blob.png	f
15	374aad6fdbec7126637900e1f3274f99	image/png	blob-c916309604bc7934354d68edfefcc699.png	MapasCulturais\\Entities\\Agent	1	2019-06-26 17:53:40	img:avatarSmall	\N	14	agent/1/file/14/blob-c916309604bc7934354d68edfefcc699.png	f
16	28aee2176d56e6c2b7f5d27c5942a63a	image/png	blob-433165e2ab64a3dca4f64b2b2e37e307.png	MapasCulturais\\Entities\\Agent	1	2019-06-26 17:53:40	img:avatarMedium	\N	14	agent/1/file/14/blob-433165e2ab64a3dca4f64b2b2e37e307.png	f
17	7a2e52a9e8bc3eaeaa17f0962e2a039c	image/png	blob-7048b90cbee81d8a2150fd3ffb11d14d.png	MapasCulturais\\Entities\\Agent	1	2019-06-26 17:53:40	img:avatarBig	\N	14	agent/1/file/14/blob-7048b90cbee81d8a2150fd3ffb11d14d.png	f
14	b6b6ffafd6a6efcfa63b23948e78437d	image/png	blob.png	MapasCulturais\\Entities\\Agent	1	2019-06-26 17:53:40	avatar	\N	\N	agent/1/blob.png	f
85	9cce30d208d3a1d1ba19951cc6a1a87c	image/jpeg	convite_aniversário_rudá_-_nascente-7-0703e2e1047519435d9b4cdca853a02d.jpg	MapasCulturais\\Entities\\Event	3	2019-07-13 21:15:35	img:avatarSmall	\N	84	event/3/file/84/convite_aniversário_rudá_-_nascente-7-0703e2e1047519435d9b4cdca853a02d.jpg	f
86	6bcd14834181aa0d9daceab3e17995fa	image/jpeg	convite_aniversário_rudá_-_nascente-7-4188811a3ba0b7cdee2a04153b9d2b58.jpg	MapasCulturais\\Entities\\Event	3	2019-07-13 21:15:35	img:avatarMedium	\N	84	event/3/file/84/convite_aniversário_rudá_-_nascente-7-4188811a3ba0b7cdee2a04153b9d2b58.jpg	f
47	7aed0da1b433cfd9d74acda6e633df14	image/png	blob-12-41c6e788502bf0db750ddd6595dcf699.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 19:32:26	img:avatarSmall	\N	46	agent/5/file/46/blob-12-41c6e788502bf0db750ddd6595dcf699.png	f
48	2b30ed3b55d3b885d53864df524f91d5	image/png	blob-12-ea34abc3c65fdc28e9cb6bfba85a47c4.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 19:32:26	img:avatarMedium	\N	46	agent/5/file/46/blob-12-ea34abc3c65fdc28e9cb6bfba85a47c4.png	f
49	5fda4893119d8a21170868c55362bce7	image/png	blob-12-a0b3baf6c2496cdf9437643f1deb6ea4.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 19:32:26	img:avatarBig	\N	46	agent/5/file/46/blob-12-a0b3baf6c2496cdf9437643f1deb6ea4.png	f
46	d336f3c9343de457f294e57884d729d5	image/png	blob-12.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 19:32:26	avatar	\N	\N	agent/5/blob-12.png	f
65	f53246dc45960540478c2ebc257f76a4	image/png	blob-2-11-87bb84c34c4fe52ef3af8e496e34f2b1.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 20:46:12	img:header	\N	64	agent/5/file/64/blob-2-11-87bb84c34c4fe52ef3af8e496e34f2b1.png	f
64	7440ea6439847c9a5cc246833d0a4a95	image/png	blob-2-11.png	MapasCulturais\\Entities\\Agent	5	2019-06-26 20:46:12	header	\N	\N	agent/5/blob-2-11.png	f
67	7aed0da1b433cfd9d74acda6e633df14	image/png	blob-12-41c6e788502bf0db750ddd6595dcf699.png	MapasCulturais\\Entities\\Space	1	2019-06-28 18:06:07	img:avatarSmall	\N	66	space/1/file/66/blob-12-41c6e788502bf0db750ddd6595dcf699.png	f
68	2b30ed3b55d3b885d53864df524f91d5	image/png	blob-12-ea34abc3c65fdc28e9cb6bfba85a47c4.png	MapasCulturais\\Entities\\Space	1	2019-06-28 18:06:07	img:avatarMedium	\N	66	space/1/file/66/blob-12-ea34abc3c65fdc28e9cb6bfba85a47c4.png	f
69	5fda4893119d8a21170868c55362bce7	image/png	blob-12-a0b3baf6c2496cdf9437643f1deb6ea4.png	MapasCulturais\\Entities\\Space	1	2019-06-28 18:06:07	img:avatarBig	\N	66	space/1/file/66/blob-12-a0b3baf6c2496cdf9437643f1deb6ea4.png	f
66	d336f3c9343de457f294e57884d729d5	image/png	blob-12.png	MapasCulturais\\Entities\\Space	1	2019-06-28 18:06:07	avatar	\N	\N	space/1/blob-12.png	f
87	def9d5bbc6c0e0d9264ea65f11619bf1	image/jpeg	convite_aniversário_rudá_-_nascente-7-2a7c12e12f283584e274732aca618581.jpg	MapasCulturais\\Entities\\Event	3	2019-07-13 21:15:35	img:avatarBig	\N	84	event/3/file/84/convite_aniversário_rudá_-_nascente-7-2a7c12e12f283584e274732aca618581.jpg	f
84	a3de47c300aa5f9769937ea1ab61b428	image/jpeg	convite_aniversário_rudá_-_nascente-7.jpg	MapasCulturais\\Entities\\Event	3	2019-07-13 21:15:35	avatar	\N	\N	event/3/convite_aniversário_rudá_-_nascente-7.jpg	f
88	525b4743bd853690e71e365c29ae51ea	image/jpeg	convite_aniversário_rudá_-_nascente-7-a132ffb20db63cf1df88756e69c8f165.jpg	MapasCulturais\\Entities\\Event	3	2019-07-16 00:39:20	img:galleryFull	\N	84	event/3/file/84/convite_aniversário_rudá_-_nascente-7-a132ffb20db63cf1df88756e69c8f165.jpg	f
90	9cce30d208d3a1d1ba19951cc6a1a87c	image/jpeg	convite_aniversário_rudá_-_nascente-7-0703e2e1047519435d9b4cdca853a02d.jpg	MapasCulturais\\Entities\\Event	6	2019-07-16 13:34:52	img:avatarSmall	\N	89	event/6/file/89/convite_aniversário_rudá_-_nascente-7-0703e2e1047519435d9b4cdca853a02d.jpg	f
91	6bcd14834181aa0d9daceab3e17995fa	image/jpeg	convite_aniversário_rudá_-_nascente-7-4188811a3ba0b7cdee2a04153b9d2b58.jpg	MapasCulturais\\Entities\\Event	6	2019-07-16 13:34:52	img:avatarMedium	\N	89	event/6/file/89/convite_aniversário_rudá_-_nascente-7-4188811a3ba0b7cdee2a04153b9d2b58.jpg	f
92	def9d5bbc6c0e0d9264ea65f11619bf1	image/jpeg	convite_aniversário_rudá_-_nascente-7-2a7c12e12f283584e274732aca618581.jpg	MapasCulturais\\Entities\\Event	6	2019-07-16 13:34:52	img:avatarBig	\N	89	event/6/file/89/convite_aniversário_rudá_-_nascente-7-2a7c12e12f283584e274732aca618581.jpg	f
89	a3de47c300aa5f9769937ea1ab61b428	image/jpeg	convite_aniversário_rudá_-_nascente-7.jpg	MapasCulturais\\Entities\\Event	6	2019-07-16 13:34:52	avatar	\N	\N	event/6/convite_aniversário_rudá_-_nascente-7.jpg	f
\.


--
-- Data for Name: geo_division; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.geo_division (id, parent_id, type, cod, name, geom) FROM stdin;
\.


--
-- Data for Name: metadata; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.metadata (object_id, object_type, key, value) FROM stdin;
\.


--
-- Data for Name: metalist; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.metalist (id, object_type, object_id, grp, title, description, value, create_timestamp, "order") FROM stdin;
\.


--
-- Data for Name: notification; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notification (id, user_id, request_id, message, create_timestamp, action_timestamp, status) FROM stdin;
\.


--
-- Data for Name: notification_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notification_meta (id, object_id, key, value) FROM stdin;
\.


--
-- Data for Name: opportunity; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.opportunity (id, parent_id, agent_id, type, name, short_description, long_description, registration_from, registration_to, published_registrations, registration_categories, create_timestamp, update_timestamp, status, subsite_id, object_type, object_id) FROM stdin;
\.


--
-- Data for Name: opportunity_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.opportunity_meta (id, object_id, key, value) FROM stdin;
\.


--
-- Data for Name: pcache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pcache (id, user_id, action, create_timestamp, object_type, object_id) FROM stdin;
\.


--
-- Data for Name: permission_cache_pending; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.permission_cache_pending (id, object_id, object_type) FROM stdin;
\.


--
-- Data for Name: project; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.project (id, name, short_description, long_description, create_timestamp, status, agent_id, is_verified, type, parent_id, registration_from, registration_to, update_timestamp, subsite_id) FROM stdin;
\.


--
-- Data for Name: project_event; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.project_event (id, event_id, project_id, type, status) FROM stdin;
\.


--
-- Data for Name: project_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.project_meta (object_id, key, value, id) FROM stdin;
\.


--
-- Data for Name: registration; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.registration (id, opportunity_id, category, agent_id, create_timestamp, sent_timestamp, status, agents_data, subsite_id, consolidated_result, number, valuers_exceptions_list) FROM stdin;
\.


--
-- Data for Name: registration_evaluation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.registration_evaluation (id, registration_id, user_id, result, evaluation_data, status) FROM stdin;
\.


--
-- Data for Name: registration_field_configuration; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.registration_field_configuration (id, opportunity_id, title, description, categories, required, field_type, field_options, max_size, display_order) FROM stdin;
\.


--
-- Data for Name: registration_file_configuration; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.registration_file_configuration (id, opportunity_id, title, description, required, categories, display_order) FROM stdin;
\.


--
-- Data for Name: registration_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.registration_meta (object_id, key, value, id) FROM stdin;
\.


--
-- Data for Name: request; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.request (id, request_uid, requester_user_id, origin_type, origin_id, destination_type, destination_id, metadata, type, create_timestamp, action_timestamp, status) FROM stdin;
\.


--
-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.role (id, usr_id, name, subsite_id) FROM stdin;
2	1	saasSuperAdmin	\N
\.


--
-- Data for Name: seal; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.seal (id, agent_id, name, short_description, long_description, valid_period, create_timestamp, status, certificate_text, update_timestamp, subsite_id) FROM stdin;
1	1	Selo Mapas	Descrição curta Selo Mapas	Descrição longa Selo Mapas	0	2019-03-07 23:54:04	1	\N	2019-03-07 00:00:00	\N
\.


--
-- Data for Name: seal_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.seal_meta (id, object_id, key, value) FROM stdin;
\.


--
-- Data for Name: seal_relation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.seal_relation (id, seal_id, object_id, create_timestamp, status, object_type, agent_id, owner_id, validate_date, renovation_request) FROM stdin;
1	1	3	2019-07-13 22:11:58	1	MapasCulturais\\Entities\\Event	1	1	2019-07-13	\N
\.


--
-- Data for Name: space; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.space (id, parent_id, location, _geo_location, name, short_description, long_description, create_timestamp, status, type, agent_id, is_verified, public, update_timestamp, subsite_id) FROM stdin;
3	\N	(139.764636499999995,35.677769099999999)	0101000020E61000007E1AF7E677786140A35E4B23C1D64140	hacklab/	o hacklab existe para existir	empresa	2019-07-05 21:32:32	1	106	1	f	f	2019-07-13 21:08:58	\N
1	\N	(-46.6950873759422009,-23.5387497500000009)	0101000020E610000061C4859FF85847C06AFAEC80EB8937C0	Igreja Nossa Senhora da Memória RAM	Ordem do Apóstolo GPU\r\nMussum Ipsum, cacilds vidis litro abertis. Todo mundo vê os porris que eu tomo, mas ninguém vê os tombis que eu levo!	Mussum Ipsum, cacilds vidis litro abertis. Todo mundo vê os porris que eu tomo, mas ninguém vê os tombis que eu levo! Pra lá , depois divoltis porris, paradis. Copo furadis é disculpa de bebadis, arcu quam euismod magna. Si num tem leite então bota uma pinga aí cumpadi!\r\n\r\nSi u mundo tá muito paradis? Toma um mé que o mundo vai girarzis! Mais vale um bebadis conhecidiss, que um alcoolatra anonimis. Paisis, filhis, espiritis santis. Em pé sem cair, deitado sem dormir, sentado sem cochilar e fazendo pose.\r\n\r\nQuem num gosta di mim que vai caçá sua turmis! Viva Forevis aptent taciti sociosqu ad litora torquent. Nullam volutpat risus nec leo commodo, ut interdum diam laoreet. Sed non consequat odio. Detraxit consequat et quo num tendi nada.	2019-06-28 18:05:52	1	84	1	f	t	2019-07-13 21:23:15	\N
2	\N	(-46.6653556865639985,-23.5473702069444002)	0101000020E6100000000009602A5547C086A13174208C37C0	Condomínio Cultural	mundiamente conhecido como Condô		2019-07-05 21:20:29	1	41	1	f	f	2019-07-10 22:22:12	\N
\.


--
-- Data for Name: space_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.space_meta (object_id, key, value, id) FROM stdin;
1	endereco	Rua Mundo Novo 342, Perdizes, São Paulo, SP	1
2	emailPublico	contato@condominiocultural.art.br	2
2	endereco	Rua Mundo Novo 342, Perdizes, São Paulo, SP	3
2	En_Nome_Logradouro	Rua Mundo Novo	4
2	En_Num	342	5
2	En_Bairro	Perdizes	6
2	En_Municipio	São Paulo	7
2	En_Estado	SP	8
2	criterios	ser gente boa	9
3	emailPublico	contato@hacklab.com.br	10
3	capacidade	25	11
3	endereco	Rua Mundo Novo 342, Perdizes, São Paulo, SP	12
3	En_Nome_Logradouro	Rua Mundo Novo	13
3	En_Num	342	14
3	En_Bairro	Perdizes	15
3	En_Municipio	São Paulo	16
3	En_Estado	SP	17
3	site	https://hacklab.com.br	18
3	facebook	https://www.facebook.com/hacklabr/	19
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: subsite; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.subsite (id, name, create_timestamp, status, agent_id, url, namespace, alias_url, verified_seals) FROM stdin;
\.


--
-- Data for Name: subsite_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.subsite_meta (object_id, key, value, id) FROM stdin;
\.


--
-- Data for Name: term; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.term (id, taxonomy, term, description) FROM stdin;
1	area	Arte Digital	
2	area	Arquitetura-Urbanismo	
3	area	Arte de Rua	
4	area	Artes Visuais	
5	area	Audiovisual	
6	tag	agente	
7	tag	LGBTs	
8	tag	teste	
9	tag	testa	
10	area	Cinema	
11	area	Cultura Cigana	
12	area	Cultura Estrangeira (imigrantes)	
13	area	Economia Criativa	
14	tag	administrados	
15	area	Arqueologia	
16	area	Artesanato	
17	linguagem	Cinema	
18	linguagem	Cultura Indígena	
19	tag	igueja	
20	tag	memória ram	
21	tag	gpu	
22	tag	RAFAEL	
23	linguagem	Artes Integradas	
24	linguagem	Cultura Tradicional	
25	tag	estudio	
26	tag	palavra	
27	tag	qualquer	
28	tag	serve	
29	tag	taga	
30	area	Circo	
31	area	Comunicação	
32	area	Cultura Digital	
33	area	Dança	
34	area	Gestão Cultural	
35	area	Música	
36	area	Outros	
37	area	Produção Cultural	
38	area	Teatro	
39	tag	condô	
40	linguagem	Cultura Digital	
41	linguagem	Outros	
42	tag	hackaton	
43	area	Televisão	
\.


--
-- Data for Name: term_relation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.term_relation (term_id, object_type, object_id, id) FROM stdin;
1	MapasCulturais\\Entities\\Agent	5	1
2	MapasCulturais\\Entities\\Agent	6	2
4	MapasCulturais\\Entities\\Agent	7	4
5	MapasCulturais\\Entities\\Agent	7	5
6	MapasCulturais\\Entities\\Agent	7	6
7	MapasCulturais\\Entities\\Agent	7	7
8	MapasCulturais\\Entities\\Agent	7	8
9	MapasCulturais\\Entities\\Agent	7	9
10	MapasCulturais\\Entities\\Agent	1	10
11	MapasCulturais\\Entities\\Agent	1	11
12	MapasCulturais\\Entities\\Agent	1	12
13	MapasCulturais\\Entities\\Agent	1	13
14	MapasCulturais\\Entities\\Agent	1	14
15	MapasCulturais\\Entities\\Agent	1	15
3	MapasCulturais\\Entities\\Agent	1	16
16	MapasCulturais\\Entities\\Agent	1	17
8	MapasCulturais\\Entities\\Agent	1	18
2	MapasCulturais\\Entities\\Space	1	22
1	MapasCulturais\\Entities\\Space	1	23
16	MapasCulturais\\Entities\\Space	1	24
19	MapasCulturais\\Entities\\Space	1	25
20	MapasCulturais\\Entities\\Space	1	26
17	MapasCulturais\\Entities\\Event	3	28
21	MapasCulturais\\Entities\\Space	1	29
22	MapasCulturais\\Entities\\Space	1	30
23	MapasCulturais\\Entities\\Event	5	31
17	MapasCulturais\\Entities\\Event	5	32
24	MapasCulturais\\Entities\\Event	5	33
25	MapasCulturais\\Entities\\Event	5	34
26	MapasCulturais\\Entities\\Event	5	35
27	MapasCulturais\\Entities\\Event	5	36
28	MapasCulturais\\Entities\\Event	5	37
29	MapasCulturais\\Entities\\Event	5	38
4	MapasCulturais\\Entities\\Space	2	39
5	MapasCulturais\\Entities\\Space	2	40
10	MapasCulturais\\Entities\\Space	2	41
30	MapasCulturais\\Entities\\Space	2	42
31	MapasCulturais\\Entities\\Space	2	43
32	MapasCulturais\\Entities\\Space	2	44
33	MapasCulturais\\Entities\\Space	2	45
13	MapasCulturais\\Entities\\Space	2	46
34	MapasCulturais\\Entities\\Space	2	47
35	MapasCulturais\\Entities\\Space	2	48
36	MapasCulturais\\Entities\\Space	2	49
37	MapasCulturais\\Entities\\Space	2	50
38	MapasCulturais\\Entities\\Space	2	51
39	MapasCulturais\\Entities\\Space	2	52
40	MapasCulturais\\Entities\\Event	6	54
41	MapasCulturais\\Entities\\Event	6	55
42	MapasCulturais\\Entities\\Event	6	56
43	MapasCulturais\\Entities\\Space	3	57
2	MapasCulturais\\Entities\\Agent	9	58
\.


--
-- Data for Name: user_app; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_app (public_key, private_key, user_id, name, status, create_timestamp, subsite_id) FROM stdin;
x6yKd7q4WLIcZNV5djrxmTo8zCimMc2F	Uy2xejAEuLgWrfUYY8KoYFsl6nwYv3si05oic80mjnGDSO00FZvNusvGAYmPOfSc	1	WP	1	2019-06-24 20:27:21	\N
\.


--
-- Data for Name: user_meta; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_meta (object_id, key, value, id) FROM stdin;
1	deleteAccountToken	54cd760dfca39f8e8c8332d7499bdb44f921dc16	1
1	localAuthenticationPassword	$2y$10$iIXeqhX.4fEAAVZPsbtRde7CFw1ChduCi8NsnXGnJc6TlelY6gf3e	2
\.


--
-- Data for Name: usr; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.usr (id, auth_provider, auth_uid, email, last_login_timestamp, create_timestamp, status, profile_id) FROM stdin;
1	1	1	Admin@local	2019-07-16 00:39:37	2019-03-07 00:00:00	1	1
\.


--
-- Data for Name: geocode_settings; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.geocode_settings (name, setting, unit, category, short_desc) FROM stdin;
\.


--
-- Data for Name: pagc_gaz; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_gaz (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_lex; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_lex (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_rules; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_rules (id, rule, is_custom) FROM stdin;
\.


--
-- Data for Name: topology; Type: TABLE DATA; Schema: topology; Owner: -
--

COPY topology.topology (id, name, srid, "precision", hasz) FROM stdin;
\.


--
-- Data for Name: layer; Type: TABLE DATA; Schema: topology; Owner: -
--

COPY topology.layer (topology_id, layer_id, schema_name, table_name, feature_column, feature_type, level, child_id) FROM stdin;
\.


--
-- Name: _mesoregiao_gid_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public._mesoregiao_gid_seq', 1, false);


--
-- Name: _microregiao_gid_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public._microregiao_gid_seq', 1, false);


--
-- Name: _municipios_gid_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public._municipios_gid_seq', 1, false);


--
-- Name: agent_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.agent_id_seq', 9, true);


--
-- Name: agent_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.agent_meta_id_seq', 8, true);


--
-- Name: agent_relation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.agent_relation_id_seq', 1, false);


--
-- Name: entity_revision_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.entity_revision_id_seq', 131, true);


--
-- Name: evaluation_method_configuration_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.evaluation_method_configuration_id_seq', 1, false);


--
-- Name: evaluationmethodconfiguration_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.evaluationmethodconfiguration_meta_id_seq', 1, false);


--
-- Name: event_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.event_id_seq', 6, true);


--
-- Name: event_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.event_meta_id_seq', 9, true);


--
-- Name: event_occurrence_cancellation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.event_occurrence_cancellation_id_seq', 1, false);


--
-- Name: event_occurrence_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.event_occurrence_id_seq', 5, true);


--
-- Name: event_occurrence_recurrence_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.event_occurrence_recurrence_id_seq', 14, true);


--
-- Name: file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.file_id_seq', 92, true);


--
-- Name: geo_division_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.geo_division_id_seq', 1, false);


--
-- Name: metalist_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.metalist_id_seq', 1, false);


--
-- Name: notification_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.notification_id_seq', 1, false);


--
-- Name: notification_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.notification_meta_id_seq', 1, false);


--
-- Name: occurrence_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.occurrence_id_seq', 100000, false);


--
-- Name: opportunity_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.opportunity_id_seq', 1, false);


--
-- Name: opportunity_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.opportunity_meta_id_seq', 1, false);


--
-- Name: pcache_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pcache_id_seq', 1, false);


--
-- Name: permission_cache_pending_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.permission_cache_pending_seq', 130, true);


--
-- Name: project_event_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_event_id_seq', 1, false);


--
-- Name: project_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_id_seq', 1, false);


--
-- Name: project_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.project_meta_id_seq', 1, false);


--
-- Name: pseudo_random_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pseudo_random_id_seq', 1, false);


--
-- Name: registration_evaluation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.registration_evaluation_id_seq', 1, false);


--
-- Name: registration_field_configuration_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.registration_field_configuration_id_seq', 1, false);


--
-- Name: registration_file_configuration_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.registration_file_configuration_id_seq', 1, false);


--
-- Name: registration_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.registration_id_seq', 1, false);


--
-- Name: registration_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.registration_meta_id_seq', 1, false);


--
-- Name: request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.request_id_seq', 1, false);


--
-- Name: revision_data_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.revision_data_id_seq', 364, true);


--
-- Name: role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.role_id_seq', 2, true);


--
-- Name: seal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.seal_id_seq', 1, false);


--
-- Name: seal_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.seal_meta_id_seq', 1, false);


--
-- Name: seal_relation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.seal_relation_id_seq', 1, true);


--
-- Name: space_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.space_id_seq', 3, true);


--
-- Name: space_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.space_meta_id_seq', 19, true);


--
-- Name: subsite_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.subsite_id_seq', 1, false);


--
-- Name: subsite_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.subsite_meta_id_seq', 1, false);


--
-- Name: term_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.term_id_seq', 43, true);


--
-- Name: term_relation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.term_relation_id_seq', 58, true);


--
-- Name: user_meta_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.user_meta_id_seq', 2, true);


--
-- Name: usr_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.usr_id_seq', 1, true);


--
-- Name: _mesoregiao _mesoregiao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._mesoregiao
    ADD CONSTRAINT _mesoregiao_pkey PRIMARY KEY (gid);


--
-- Name: _microregiao _microregiao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._microregiao
    ADD CONSTRAINT _microregiao_pkey PRIMARY KEY (gid);


--
-- Name: _municipios _municipios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public._municipios
    ADD CONSTRAINT _municipios_pkey PRIMARY KEY (gid);


--
-- Name: agent_meta agent_meta_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_meta
    ADD CONSTRAINT agent_meta_pk PRIMARY KEY (id);


--
-- Name: agent agent_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent
    ADD CONSTRAINT agent_pk PRIMARY KEY (id);


--
-- Name: agent_relation agent_relation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_relation
    ADD CONSTRAINT agent_relation_pkey PRIMARY KEY (id);


--
-- Name: db_update db_update_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.db_update
    ADD CONSTRAINT db_update_pk PRIMARY KEY (name);


--
-- Name: entity_revision_data entity_revision_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision_data
    ADD CONSTRAINT entity_revision_data_pkey PRIMARY KEY (id);


--
-- Name: entity_revision entity_revision_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision
    ADD CONSTRAINT entity_revision_pkey PRIMARY KEY (id);


--
-- Name: entity_revision_revision_data entity_revision_revision_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision_revision_data
    ADD CONSTRAINT entity_revision_revision_data_pkey PRIMARY KEY (revision_id, revision_data_id);


--
-- Name: evaluation_method_configuration evaluation_method_configuration_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.evaluation_method_configuration
    ADD CONSTRAINT evaluation_method_configuration_pkey PRIMARY KEY (id);


--
-- Name: evaluationmethodconfiguration_meta evaluationmethodconfiguration_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.evaluationmethodconfiguration_meta
    ADD CONSTRAINT evaluationmethodconfiguration_meta_pkey PRIMARY KEY (id);


--
-- Name: event_meta event_meta_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_meta
    ADD CONSTRAINT event_meta_pk PRIMARY KEY (id);


--
-- Name: event_occurrence_cancellation event_occurrence_cancellation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_cancellation
    ADD CONSTRAINT event_occurrence_cancellation_pkey PRIMARY KEY (id);


--
-- Name: event_occurrence event_occurrence_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence
    ADD CONSTRAINT event_occurrence_pkey PRIMARY KEY (id);


--
-- Name: event_occurrence_recurrence event_occurrence_recurrence_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_recurrence
    ADD CONSTRAINT event_occurrence_recurrence_pkey PRIMARY KEY (id);


--
-- Name: event event_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_pk PRIMARY KEY (id);


--
-- Name: file file_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file
    ADD CONSTRAINT file_pk PRIMARY KEY (id);


--
-- Name: geo_division geo_divisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.geo_division
    ADD CONSTRAINT geo_divisions_pkey PRIMARY KEY (id);


--
-- Name: metadata metadata_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metadata
    ADD CONSTRAINT metadata_pk PRIMARY KEY (object_id, object_type, key);


--
-- Name: metalist metalist_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metalist
    ADD CONSTRAINT metalist_pk PRIMARY KEY (id);


--
-- Name: notification_meta notification_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_meta
    ADD CONSTRAINT notification_meta_pkey PRIMARY KEY (id);


--
-- Name: notification notification_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification
    ADD CONSTRAINT notification_pk PRIMARY KEY (id);


--
-- Name: opportunity_meta opportunity_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunity_meta
    ADD CONSTRAINT opportunity_meta_pkey PRIMARY KEY (id);


--
-- Name: opportunity opportunity_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunity
    ADD CONSTRAINT opportunity_pkey PRIMARY KEY (id);


--
-- Name: pcache pcache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pcache
    ADD CONSTRAINT pcache_pkey PRIMARY KEY (id);


--
-- Name: permission_cache_pending permission_cache_pending_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permission_cache_pending
    ADD CONSTRAINT permission_cache_pending_pkey PRIMARY KEY (id);


--
-- Name: project_event project_event_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_event
    ADD CONSTRAINT project_event_pk PRIMARY KEY (id);


--
-- Name: project_meta project_meta_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_meta
    ADD CONSTRAINT project_meta_pk PRIMARY KEY (id);


--
-- Name: project project_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_pk PRIMARY KEY (id);


--
-- Name: registration_evaluation registration_evaluation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_evaluation
    ADD CONSTRAINT registration_evaluation_pkey PRIMARY KEY (id);


--
-- Name: registration_field_configuration registration_field_configuration_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_field_configuration
    ADD CONSTRAINT registration_field_configuration_pkey PRIMARY KEY (id);


--
-- Name: registration_file_configuration registration_file_configuration_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_file_configuration
    ADD CONSTRAINT registration_file_configuration_pkey PRIMARY KEY (id);


--
-- Name: registration_meta registration_meta_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_meta
    ADD CONSTRAINT registration_meta_pk PRIMARY KEY (id);


--
-- Name: registration registration_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration
    ADD CONSTRAINT registration_pkey PRIMARY KEY (id);


--
-- Name: request request_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request
    ADD CONSTRAINT request_pk PRIMARY KEY (id);


--
-- Name: role role_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_pk PRIMARY KEY (id);


--
-- Name: subsite saas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsite
    ADD CONSTRAINT saas_pkey PRIMARY KEY (id);


--
-- Name: seal_meta seal_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal_meta
    ADD CONSTRAINT seal_meta_pkey PRIMARY KEY (id);


--
-- Name: seal seal_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal
    ADD CONSTRAINT seal_pkey PRIMARY KEY (id);


--
-- Name: seal_relation seal_relation_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal_relation
    ADD CONSTRAINT seal_relation_pkey PRIMARY KEY (id);


--
-- Name: space_meta space_meta_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space_meta
    ADD CONSTRAINT space_meta_pk PRIMARY KEY (id);


--
-- Name: space space_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space
    ADD CONSTRAINT space_pk PRIMARY KEY (id);


--
-- Name: subsite_meta subsite_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsite_meta
    ADD CONSTRAINT subsite_meta_pkey PRIMARY KEY (id);


--
-- Name: term term_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.term
    ADD CONSTRAINT term_pk PRIMARY KEY (id);


--
-- Name: term_relation term_relation_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.term_relation
    ADD CONSTRAINT term_relation_pk PRIMARY KEY (id);


--
-- Name: user_app user_app_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app
    ADD CONSTRAINT user_app_pk PRIMARY KEY (public_key);


--
-- Name: user_meta user_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_meta
    ADD CONSTRAINT user_meta_pkey PRIMARY KEY (id);


--
-- Name: usr usr_pk; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usr
    ADD CONSTRAINT usr_pk PRIMARY KEY (id);


--
-- Name: agent_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_meta_key_idx ON public.agent_meta USING btree (key);


--
-- Name: agent_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_meta_owner_idx ON public.agent_meta USING btree (object_id);


--
-- Name: agent_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_meta_owner_key_idx ON public.agent_meta USING btree (object_id, key);


--
-- Name: agent_relation_all; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_relation_all ON public.agent_relation USING btree (agent_id, object_type, object_id);


--
-- Name: alias_url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alias_url_index ON public.subsite USING btree (alias_url);


--
-- Name: evaluationmethodconfiguration_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX evaluationmethodconfiguration_meta_owner_idx ON public.evaluationmethodconfiguration_meta USING btree (object_id);


--
-- Name: evaluationmethodconfiguration_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX evaluationmethodconfiguration_meta_owner_key_idx ON public.evaluationmethodconfiguration_meta USING btree (object_id, key);


--
-- Name: event_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX event_meta_key_idx ON public.event_meta USING btree (key);


--
-- Name: event_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX event_meta_owner_idx ON public.event_meta USING btree (object_id);


--
-- Name: event_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX event_meta_owner_key_idx ON public.event_meta USING btree (object_id, key);


--
-- Name: event_occurrence_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX event_occurrence_status_index ON public.event_occurrence USING btree (status);


--
-- Name: file_group_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX file_group_index ON public.file USING btree (grp);


--
-- Name: file_owner_grp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX file_owner_grp_index ON public.file USING btree (object_type, object_id, grp);


--
-- Name: file_owner_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX file_owner_index ON public.file USING btree (object_type, object_id);


--
-- Name: geo_divisions_geom_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX geo_divisions_geom_idx ON public.geo_division USING gist (geom);


--
-- Name: idx_209c792e9a34590f; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_209c792e9a34590f ON public.registration_file_configuration USING btree (opportunity_id);


--
-- Name: idx_22781144c79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_22781144c79c849a ON public.user_app USING btree (subsite_id);


--
-- Name: idx_268b9c9dc79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_268b9c9dc79c849a ON public.agent USING btree (subsite_id);


--
-- Name: idx_2972c13ac79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_2972c13ac79c849a ON public.space USING btree (subsite_id);


--
-- Name: idx_2e186c5c833d8f43; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_2e186c5c833d8f43 ON public.registration_evaluation USING btree (registration_id);


--
-- Name: idx_2e186c5ca76ed395; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_2e186c5ca76ed395 ON public.registration_evaluation USING btree (user_id);


--
-- Name: idx_2e30ae30c79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_2e30ae30c79c849a ON public.seal USING btree (subsite_id);


--
-- Name: idx_2fb3d0eec79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_2fb3d0eec79c849a ON public.project USING btree (subsite_id);


--
-- Name: idx_3bae0aa7c79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3bae0aa7c79c849a ON public.event USING btree (subsite_id);


--
-- Name: idx_3d853098232d562b; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3d853098232d562b ON public.pcache USING btree (object_id);


--
-- Name: idx_3d853098a76ed395; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3d853098a76ed395 ON public.pcache USING btree (user_id);


--
-- Name: idx_57698a6ac79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_57698a6ac79c849a ON public.role USING btree (subsite_id);


--
-- Name: idx_60c85cb1166d1f9c; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_60c85cb1166d1f9c ON public.registration_field_configuration USING btree (opportunity_id);


--
-- Name: idx_60c85cb19a34590f; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_60c85cb19a34590f ON public.registration_field_configuration USING btree (opportunity_id);


--
-- Name: idx_62a8a7a73414710b; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_62a8a7a73414710b ON public.registration USING btree (agent_id);


--
-- Name: idx_62a8a7a79a34590f; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_62a8a7a79a34590f ON public.registration USING btree (opportunity_id);


--
-- Name: idx_62a8a7a7c79c849a; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_62a8a7a7c79c849a ON public.registration USING btree (subsite_id);


--
-- Name: notification_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_meta_key_idx ON public.notification_meta USING btree (key);


--
-- Name: notification_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_meta_owner_idx ON public.notification_meta USING btree (object_id);


--
-- Name: notification_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notification_meta_owner_key_idx ON public.notification_meta USING btree (object_id, key);


--
-- Name: opportunity_entity_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX opportunity_entity_idx ON public.opportunity USING btree (object_type, object_id);


--
-- Name: opportunity_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX opportunity_meta_owner_idx ON public.opportunity_meta USING btree (object_id);


--
-- Name: opportunity_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX opportunity_meta_owner_key_idx ON public.opportunity_meta USING btree (object_id, key);


--
-- Name: opportunity_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX opportunity_owner_idx ON public.opportunity USING btree (agent_id);


--
-- Name: opportunity_parent_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX opportunity_parent_idx ON public.opportunity USING btree (parent_id);


--
-- Name: owner_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX owner_index ON public.term_relation USING btree (object_type, object_id);


--
-- Name: pcache_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pcache_owner_idx ON public.pcache USING btree (object_type, object_id);


--
-- Name: pcache_permission_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pcache_permission_idx ON public.pcache USING btree (object_type, object_id, action);


--
-- Name: pcache_permission_user_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX pcache_permission_user_idx ON public.pcache USING btree (object_type, object_id, action, user_id);


--
-- Name: project_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_meta_key_idx ON public.project_meta USING btree (key);


--
-- Name: project_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_meta_owner_idx ON public.project_meta USING btree (object_id);


--
-- Name: project_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX project_meta_owner_key_idx ON public.project_meta USING btree (object_id, key);


--
-- Name: registration_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX registration_meta_owner_idx ON public.registration_meta USING btree (object_id);


--
-- Name: registration_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX registration_meta_owner_key_idx ON public.registration_meta USING btree (object_id, key);


--
-- Name: request_uid; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX request_uid ON public.request USING btree (request_uid);


--
-- Name: requester_user_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX requester_user_index ON public.request USING btree (requester_user_id, origin_type, origin_id);


--
-- Name: seal_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX seal_meta_key_idx ON public.seal_meta USING btree (key);


--
-- Name: seal_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX seal_meta_owner_idx ON public.seal_meta USING btree (object_id);


--
-- Name: seal_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX seal_meta_owner_key_idx ON public.seal_meta USING btree (object_id, key);


--
-- Name: space_location; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX space_location ON public.space USING gist (_geo_location);


--
-- Name: space_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX space_meta_key_idx ON public.space_meta USING btree (key);


--
-- Name: space_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX space_meta_owner_idx ON public.space_meta USING btree (object_id);


--
-- Name: space_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX space_meta_owner_key_idx ON public.space_meta USING btree (object_id, key);


--
-- Name: space_type; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX space_type ON public.space USING btree (type);


--
-- Name: subsite_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsite_meta_key_idx ON public.subsite_meta USING btree (key);


--
-- Name: subsite_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsite_meta_owner_idx ON public.subsite_meta USING btree (object_id);


--
-- Name: subsite_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subsite_meta_owner_key_idx ON public.subsite_meta USING btree (object_id, key);


--
-- Name: taxonomy_term_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX taxonomy_term_unique ON public.term USING btree (taxonomy, term);


--
-- Name: uniq_330cb54c9a34590f; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_330cb54c9a34590f ON public.evaluation_method_configuration USING btree (opportunity_id);


--
-- Name: url_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX url_index ON public.subsite USING btree (url);


--
-- Name: user_meta_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_meta_key_idx ON public.user_meta USING btree (key);


--
-- Name: user_meta_owner_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_meta_owner_idx ON public.user_meta USING btree (object_id);


--
-- Name: user_meta_owner_key_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_meta_owner_key_idx ON public.user_meta USING btree (object_id, key);


--
-- Name: agent agent_agent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent
    ADD CONSTRAINT agent_agent_fk FOREIGN KEY (parent_id) REFERENCES public.agent(id);


--
-- Name: agent_relation agent_relation_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_relation
    ADD CONSTRAINT agent_relation_fk FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: entity_revision entity_revision_usr_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision
    ADD CONSTRAINT entity_revision_usr_fk FOREIGN KEY (user_id) REFERENCES public.usr(id);


--
-- Name: event event_agent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT event_agent_fk FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: event_occurrence event_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence
    ADD CONSTRAINT event_fk FOREIGN KEY (event_id) REFERENCES public.event(id);


--
-- Name: event_occurrence_cancellation event_occurrence_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_cancellation
    ADD CONSTRAINT event_occurrence_fk FOREIGN KEY (event_occurrence_id) REFERENCES public.event_occurrence(id) ON DELETE CASCADE;


--
-- Name: event_occurrence_recurrence event_occurrence_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence_recurrence
    ADD CONSTRAINT event_occurrence_fk FOREIGN KEY (event_occurrence_id) REFERENCES public.event_occurrence(id) ON DELETE CASCADE;


--
-- Name: project_event event_project_event_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_event
    ADD CONSTRAINT event_project_event_fk FOREIGN KEY (event_id) REFERENCES public.event(id);


--
-- Name: file file_file_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file
    ADD CONSTRAINT file_file_fk FOREIGN KEY (parent_id) REFERENCES public.file(id);


--
-- Name: registration_meta fk_18cc03e9232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_meta
    ADD CONSTRAINT fk_18cc03e9232d562b FOREIGN KEY (object_id) REFERENCES public.registration(id) ON DELETE CASCADE;


--
-- Name: registration_file_configuration fk_209c792e9a34590f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_file_configuration
    ADD CONSTRAINT fk_209c792e9a34590f FOREIGN KEY (opportunity_id) REFERENCES public.opportunity(id);


--
-- Name: user_app fk_22781144c79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app
    ADD CONSTRAINT fk_22781144c79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: agent fk_268b9c9dc79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent
    ADD CONSTRAINT fk_268b9c9dc79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: space fk_2972c13ac79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space
    ADD CONSTRAINT fk_2972c13ac79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: opportunity_meta fk_2bb06d08232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunity_meta
    ADD CONSTRAINT fk_2bb06d08232d562b FOREIGN KEY (object_id) REFERENCES public.opportunity(id) ON DELETE CASCADE;


--
-- Name: registration_evaluation fk_2e186c5c833d8f43; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_evaluation
    ADD CONSTRAINT fk_2e186c5c833d8f43 FOREIGN KEY (registration_id) REFERENCES public.registration(id) ON DELETE CASCADE;


--
-- Name: registration_evaluation fk_2e186c5ca76ed395; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_evaluation
    ADD CONSTRAINT fk_2e186c5ca76ed395 FOREIGN KEY (user_id) REFERENCES public.usr(id) ON DELETE CASCADE;


--
-- Name: seal fk_2e30ae30c79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal
    ADD CONSTRAINT fk_2e30ae30c79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: evaluation_method_configuration fk_330cb54c9a34590f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.evaluation_method_configuration
    ADD CONSTRAINT fk_330cb54c9a34590f FOREIGN KEY (opportunity_id) REFERENCES public.opportunity(id);


--
-- Name: event fk_3bae0aa7c79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT fk_3bae0aa7c79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: pcache fk_3d853098a76ed395; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pcache
    ADD CONSTRAINT fk_3d853098a76ed395 FOREIGN KEY (user_id) REFERENCES public.usr(id);


--
-- Name: role fk_57698a6ac69d3fb; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT fk_57698a6ac69d3fb FOREIGN KEY (usr_id) REFERENCES public.usr(id);


--
-- Name: role fk_57698a6ac79c849a; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT fk_57698a6ac79c849a FOREIGN KEY (subsite_id) REFERENCES public.subsite(id);


--
-- Name: registration_field_configuration fk_60c85cb19a34590f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration_field_configuration
    ADD CONSTRAINT fk_60c85cb19a34590f FOREIGN KEY (opportunity_id) REFERENCES public.opportunity(id);


--
-- Name: registration fk_62a8a7a73414710b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration
    ADD CONSTRAINT fk_62a8a7a73414710b FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: registration fk_62a8a7a79a34590f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.registration
    ADD CONSTRAINT fk_62a8a7a79a34590f FOREIGN KEY (opportunity_id) REFERENCES public.opportunity(id);


--
-- Name: notification_meta fk_6fce5f0f232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification_meta
    ADD CONSTRAINT fk_6fce5f0f232d562b FOREIGN KEY (object_id) REFERENCES public.notification(id) ON DELETE CASCADE;


--
-- Name: subsite_meta fk_780702f5232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsite_meta
    ADD CONSTRAINT fk_780702f5232d562b FOREIGN KEY (object_id) REFERENCES public.subsite(id) ON DELETE CASCADE;


--
-- Name: agent_meta fk_7a69aed6232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_meta
    ADD CONSTRAINT fk_7a69aed6232d562b FOREIGN KEY (object_id) REFERENCES public.agent(id) ON DELETE CASCADE;


--
-- Name: opportunity fk_8389c3d73414710b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunity
    ADD CONSTRAINT fk_8389c3d73414710b FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: seal_meta fk_a92e5e22232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal_meta
    ADD CONSTRAINT fk_a92e5e22232d562b FOREIGN KEY (object_id) REFERENCES public.seal(id) ON DELETE CASCADE;


--
-- Name: user_meta fk_ad7358fc232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_meta
    ADD CONSTRAINT fk_ad7358fc232d562b FOREIGN KEY (object_id) REFERENCES public.usr(id) ON DELETE CASCADE;


--
-- Name: space_meta fk_bc846ebf232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space_meta
    ADD CONSTRAINT fk_bc846ebf232d562b FOREIGN KEY (object_id) REFERENCES public.space(id) ON DELETE CASCADE;


--
-- Name: event_meta fk_c839589e232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_meta
    ADD CONSTRAINT fk_c839589e232d562b FOREIGN KEY (object_id) REFERENCES public.event(id) ON DELETE CASCADE;


--
-- Name: evaluationmethodconfiguration_meta fk_d7edf8b2232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.evaluationmethodconfiguration_meta
    ADD CONSTRAINT fk_d7edf8b2232d562b FOREIGN KEY (object_id) REFERENCES public.evaluation_method_configuration(id) ON DELETE CASCADE;


--
-- Name: project_meta fk_ee63dc2d232d562b; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_meta
    ADD CONSTRAINT fk_ee63dc2d232d562b FOREIGN KEY (object_id) REFERENCES public.project(id) ON DELETE CASCADE;


--
-- Name: notification notification_request_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification
    ADD CONSTRAINT notification_request_fk FOREIGN KEY (request_id) REFERENCES public.request(id);


--
-- Name: notification notification_user_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notification
    ADD CONSTRAINT notification_user_fk FOREIGN KEY (user_id) REFERENCES public.usr(id);


--
-- Name: opportunity opportunity_parent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.opportunity
    ADD CONSTRAINT opportunity_parent_fk FOREIGN KEY (parent_id) REFERENCES public.opportunity(id);


--
-- Name: project project_agent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_agent_fk FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: event project_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event
    ADD CONSTRAINT project_fk FOREIGN KEY (project_id) REFERENCES public.project(id);


--
-- Name: project_event project_project_event_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project_event
    ADD CONSTRAINT project_project_event_fk FOREIGN KEY (project_id) REFERENCES public.project(id);


--
-- Name: project project_project_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_project_fk FOREIGN KEY (parent_id) REFERENCES public.project(id);


--
-- Name: request requester_user_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.request
    ADD CONSTRAINT requester_user_fk FOREIGN KEY (requester_user_id) REFERENCES public.usr(id);


--
-- Name: entity_revision_revision_data revision_data_entity_revision_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision_revision_data
    ADD CONSTRAINT revision_data_entity_revision_fk FOREIGN KEY (revision_id) REFERENCES public.entity_revision(id);


--
-- Name: entity_revision_revision_data revision_data_revision_data_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entity_revision_revision_data
    ADD CONSTRAINT revision_data_revision_data_fk FOREIGN KEY (revision_data_id) REFERENCES public.entity_revision_data(id);


--
-- Name: subsite_meta saas_saas_meta_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subsite_meta
    ADD CONSTRAINT saas_saas_meta_fk FOREIGN KEY (object_id) REFERENCES public.subsite(id);


--
-- Name: seal seal_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal
    ADD CONSTRAINT seal_fk FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: seal_relation seal_relation_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seal_relation
    ADD CONSTRAINT seal_relation_fk FOREIGN KEY (seal_id) REFERENCES public.seal(id);


--
-- Name: space space_agent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space
    ADD CONSTRAINT space_agent_fk FOREIGN KEY (agent_id) REFERENCES public.agent(id);


--
-- Name: event_occurrence space_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.event_occurrence
    ADD CONSTRAINT space_fk FOREIGN KEY (space_id) REFERENCES public.space(id);


--
-- Name: space space_space_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.space
    ADD CONSTRAINT space_space_fk FOREIGN KEY (parent_id) REFERENCES public.space(id);


--
-- Name: term_relation term_term_relation_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.term_relation
    ADD CONSTRAINT term_term_relation_fk FOREIGN KEY (term_id) REFERENCES public.term(id);


--
-- Name: usr user_profile_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usr
    ADD CONSTRAINT user_profile_fk FOREIGN KEY (profile_id) REFERENCES public.agent(id);


--
-- Name: agent usr_agent_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent
    ADD CONSTRAINT usr_agent_fk FOREIGN KEY (user_id) REFERENCES public.usr(id);


--
-- Name: user_app usr_user_app_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_app
    ADD CONSTRAINT usr_user_app_fk FOREIGN KEY (user_id) REFERENCES public.usr(id);


--
-- PostgreSQL database dump complete
--

