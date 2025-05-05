#!/bin/bash

# コンテナ内でPHPスクリプトを実行
docker exec -it matching-app-app-1 php /var/www/html/scripts/generate_test_users.php
