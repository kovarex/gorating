#!/bin/bash
# Makes gorating_php and gorating_kpp fresh copies of the made-up data snapshot, with the kpp
# version's migrations (migrations/) on both: the PHP site runs on the changed database too.
ENV=${GORATING_ENV:?set GORATING_ENV to the local setup: mariadb/ and schema/fixture-snapshot.sql}
M=$ENV/mariadb/bin/mariadb.exe
HERE=$(dirname "$0")
for db in gorating_php gorating_kpp; do
  $M -h127.0.0.1 -P3307 -uroot -e "DROP DATABASE IF EXISTS $db; CREATE DATABASE $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>/dev/null
  $M -h127.0.0.1 -P3307 -uroot --default-character-set=utf8mb4 $db < $ENV/schema/fixture-snapshot.sql 2>/dev/null
  for migration in "$HERE"/../migrations/*.sql; do
    $M -h127.0.0.1 -P3307 -uroot --default-character-set=utf8mb4 $db < "$migration" || exit 1
  done
done
