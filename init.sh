#!/bin/bash

# THIS IS INITIAL SCRIPT MUST BE RUN AFTER CLONE THE REPO

docker network create gis-apps-net

docker compose up -d

# must wait until postgres startup correctly
sleep 5

docker exec -it gis-postgres ./setupdb.sh
