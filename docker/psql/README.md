# install postgis
- apt install postgis postgresql-16-postgis-3
## reference
- https://hub.docker.com/_/postgres
- https://github.com/postgis/docker-postgis/blob/81a0b55/14-3.2/Dockerfile
- https://computingforgeeks.com/how-to-install-postgis-on-debian/

# install pgrouting
- apt install postgresql-16-pgrouting postgresql-16-pgrouting-dbgsym postgresql-16-pgrouting-doc postgresql-16-pgrouting-scripts
## reference
- https://packages.debian.org/search?keywords=pgrouting

# install GDAL (not required, just skip this)
- https://packages.debian.org/source/stable/gdal
## use ogr2ogr to export from geoJSON file to pgsql table
- https://youtu.be/LmzIMIOxDi8?si=2Z1D_GrNFZdrvUFz&t=339
- ogr2ogr -select 'name,highway,oneway,surface' -lco GEOMETRY_NAME=the_geom -lco FID=id -f PostgreSQL PG:"dbname=routing user=<user>" -nln edges roads.geojson
  -select ‘name,highway,oneway,surface’: Select the desired attributes/fields only from the data file. Other attributes in the data will not be imported
  -f PostgreSQL PG:”dbname=routing user=<user>: Load the data into Postgres with <user> and db routing
  -lco GEOMETRY_NAME=the_geom: Store the geometry in a field named the_geom
  -nlco FID=id: Store the feature identifier in a field named id
  -nln edges: Store the data in a table called edges
GDAL (Geospatial Data Abstraction Library)
GDAL merupakan pustaka yang dibutuhkan sistem (low-level) untuk manipulasi geospatial data. Namun disini tidak semua binary program GDAL dibutuhkan, hanya ogr2ogr yang dimana untuk mengkonversi features yang ada di file geoJSON ke beragam file format. Pada kasus ini diexport ke postgreSQL table.
## reference
- https://gdal.org/download.html#debian
- https://gdal.org/programs/ogr2ogr.html

# when to interact with postgres database from cli, make sure the user loged in as user postgres
1. su - postgres
2. sudo -u postgres -i (?) -> is possible using sudo for switch user ?, found from https://stackoverflow.com/questions/11919391/postgresql-error-fatal-role-username-does-not-exist

# create database
createdb bus_routing -> if not exist

# add extension postgis to database bus_routing
psql -U postgres -c "CREATE EXTENSION postgis;" bus_routing

# add extension pgrouting to database bus_routing
psql -U postgres -c "CREATE EXTENSION pgrouting;" bus_routing

# SQL Query dari TablePlus
-- analyze the graph to make sure the edges is valid
SELECT pgr_analyzegraph('edges', 0.00001, 'wkb_geometry');

-- if not valid, we have to create node network, so the edge will valid if we analyze the graph
SELECT pgr_nodeNetwork('edges', 0.00001, 'id', 'wkb_geometry');

-- create topology first, after that analyze graph will ok
SELECT pgr_createTopology('edges_noded', 0.00001, 'wkb_geometry', 'id', 'source', 'target');

-- analyze the graph to make sure the edges is valid
SELECT pgr_analyzegraph('edges_noded', 0.00001, 'wkb_geometry');

ALTER TABLE "public"."edges_noded" ADD COLUMN cost INT4, ADD COLUMN reverse_cost INT4;

UPDATE edges_noded SET cost = ST_Length(ST_Transform(wkb_geometry, 4326)::geography) / 1000;

SELECT * FROM pgr_dijkstra('SELECT id,source,target,cost FROM edges_noded',1,3,false);

-- how implement dijkstra with lat long ?
-- 106.821490,-6.308167
-- 106.830541,-6.124769

SELECT
  v.id,
  v.the_geom
FROM
  edges_noded_vertices_pgr AS v,
  edges_noded AS e
WHERE
  v.id = (SELECT
            id
          FROM edges_noded_vertices_pgr
          ORDER BY the_geom <-> ST_SetSRID(ST_MakePoint(106.830541,-6.124769), 4326) LIMIT 1)
  AND (e.source = v.id OR e.target = v.id) -- memastikan bahwa vertex yang didapat terdapat di source dan target
GROUP BY v.id, v.the_geom;


SELECT
	-- r.seq, r.node, r.edge, r.cost,
	-- 	e.wkb_geometry
	min(r.seq) AS seq,
	e.old_id AS id,
	-- e.name,
	sum(e.cost) AS
	COST,
	ST_Collect (e.wkb_geometry) AS geom
FROM
	pgr_dijkstra ('SELECT id,source,target,cost FROM edges_noded',
		182,
		177,
		FALSE) AS r,
	edges_noded AS e
WHERE
	r.edge = e.id
GROUP BY
	e.old_id;
--,e.name,e.type
SELECT
	r.seq,
	r.node,
	r.edge,
	r.cost,
	e.wkb_geometry,
	eo.name
FROM
	pgr_dijkstra ('SELECT id,source,target,cost FROM edges_noded',
		182,
		177,
		FALSE) AS r
	INNER JOIN edges_noded AS e ON r.edge = e.id
	INNER JOIN edges AS eo ON e.old_id = eo.id;

select ST_LineFromWKB(e.wkb_geometry, 4326) from edges_noded AS e limit 1;
select ST_AsText(e.wkb_geometry, 4326) from edges_noded AS e limit 1;
