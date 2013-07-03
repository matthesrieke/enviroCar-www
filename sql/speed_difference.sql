-- Table: speed_difference

-- DROP TABLE speed_difference;

CREATE TABLE speed_difference
(
  id serial NOT NULL,
  maxspeed double precision,
  avg_speed double precision,
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
ALTER TABLE speed_difference
  OWNER TO cario;
