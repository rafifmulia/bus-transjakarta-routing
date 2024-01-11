FROM postgres:16.1-bookworm

RUN apt-get update && apt-get install -y postgis \
        postgresql-16-postgis-3 \
        postgresql-16-pgrouting \
        postgresql-16-pgrouting-dbgsym \
        postgresql-16-pgrouting-doc \
        postgresql-16-pgrouting-scripts

COPY --chmod=400 ./jalur_koridor_transjakarta.sql /jalur_koridor_transjakarta.sql

COPY --chmod=100 ./setupdb.sh /setupdb.sh

CMD ["postgres"]