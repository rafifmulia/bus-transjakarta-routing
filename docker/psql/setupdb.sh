#!/bin/bash

# add extension postgis to database bus_routing
psql -U postgres -c "CREATE EXTENSION postgis;" bus_routing

# add extension pgrouting to database bus_routing
psql -U postgres -c "CREATE EXTENSION pgrouting;" bus_routing

# import edges table
psql -U postgres bus_routing < /jalur_koridor_transjakarta.sql

# relokasi table tahap awal
psql -U postgres -c "ALTER TABLE \"public\".\"jalur_koridor_transjakarta\" RENAME TO \"edges\";" bus_routing
psql -U postgres -c "ALTER TABLE \"public\".\"edges\" RENAME COLUMN \"ogc_fid\" TO \"id\";" bus_routing

# analyze the graph to make sure the edges is valid
psql -U postgres -c "SELECT pgr_analyzegraph('edges', 0.00001, 'wkb_geometry');" bus_routing

# if not valid, we have to create node network, so the edge will valid if we analyze the graph
psql -U postgres -c "SELECT pgr_nodeNetwork('edges', 0.00001, 'id', 'wkb_geometry');" bus_routing

# analyze the graph to make sure the edges is valid
psql -U postgres -c "SELECT pgr_analyzegraph('edges_noded', 0.00001, 'wkb_geometry');" bus_routing

# create topology (titik yang terhubung ke jaringan / graph)
psql -U postgres -c "SELECT pgr_createTopology('edges_noded', 0.00001, 'wkb_geometry', 'id', 'source', 'target');" bus_routing

# relokasi table untuk perhitungan biaya jarak antar titik awal yang ada di line tersebut ke titik akhir yang ada di line tersebut
# jika hanya titik awal dan titik akhir, costnya adalah 0
psql -U postgres -c "ALTER TABLE \"public\".\"edges_noded\" ADD COLUMN cost INT4, ADD COLUMN reverse_cost INT4;" bus_routing

# determine cost
psql -U postgres -c "UPDATE edges_noded SET cost = ST_Length(ST_Transform(wkb_geometry, 4326)::geography) / 1000;" bus_routing
