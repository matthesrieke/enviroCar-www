-- Table: roads

-- DROP TABLE roads;

CREATE TABLE roads
(
  gid serial NOT NULL,
  osm_id double precision,
  name character varying(48),
  ref character varying(16),
  type character varying(16),
  oneway smallint,
  bridge smallint,
  tunnel smallint,
  maxspeed smallint,
  the_geom geometry,
  CONSTRAINT roads_pkey PRIMARY KEY (gid),
  CONSTRAINT enforce_dims_the_geom CHECK (st_ndims(the_geom) = 2),
  CONSTRAINT enforce_geotype_the_geom CHECK (geometrytype(the_geom) = 'MULTILINESTRING'::text OR the_geom IS NULL),
  CONSTRAINT enforce_srid_the_geom CHECK (st_srid(the_geom) = 4326)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE roads
  OWNER TO cario;