-- Table: tracks

-- DROP TABLE tracks;

CREATE TABLE tracks
(
  id serial NOT NULL,
  speed double precision,
  co2 double precision,
  the_geom geometry,
  measurements integer,
  osm_id double precision,
  road_segment integer,
  CONSTRAINT enforce_dims_the_geom CHECK (st_ndims(the_geom) = 2),
  CONSTRAINT enforce_srid_the_geom CHECK (st_srid(the_geom) = 4326)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE tracks
  OWNER TO cario;