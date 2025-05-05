# マッチングアプリ

OpenSearchとDynamoDBを使用したシンプルなマッチングアプリケーションです。

## 必要条件

- PHP 8.1以上
- Composer
- Docker
- Docker Compose

## ローカル環境での実行

1. Docker環境の起動
```bash
docker-compose up -d
```

2. DynamoDBテーブルの作成
```bash
aws dynamodb create-table \
    --endpoint-url http://localhost:8000 \
    --table-name matching_users \
    --attribute-definitions AttributeName=id,AttributeType=S \
    --key-schema AttributeName=id,KeyType=HASH \
    --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5
```

3. OpenSearchインデックスの作成
```bash
curl -X PUT "http://localhost:9200/matching_users" -H 'Content-Type: application/json' -d'
{
  "mappings": {
    "properties": {
      "id": { "type": "keyword" },
      "name": { "type": "text" },
      "age": { "type": "integer" },
      "gender": { "type": "keyword" },
      "interests": { "type": "keyword" },
      "preferences": { "type": "object" },
      "location": { "type": "keyword" },
      "created_at": { "type": "date" },
      "updated_at": { "type": "date" }
    }
  }
}'
```

4. アプリケーションの確認
ブラウザで http://localhost:8080 にアクセスして、アプリケーションが動作していることを確認します。

## APIエンドポイント

### ユーザー登録
```
POST http://localhost:8080/users
```

リクエストボディ:
```json
{
  "name": "ユーザー名",
  "age": 25,
  "gender": "male",
  "interests": ["スポーツ", "音楽", "旅行"],
  "preferences": {
    "age_min": 20,
    "age_max": 30,
    "gender": "female"
  },
  "location": "東京"
}
```

### マッチング検索
```
GET http://localhost:8080/users/{id}/matches
```

## 開発環境の停止
```bash
docker-compose down
```

## 無料リソースの使用

このアプリケーションは以下の無料リソースを使用しています：

- AWS Free TierのDynamoDB
  - 25 WCUと25 RCU
  - 25GBのストレージ

- AWS Free TierのOpenSearch
  - t2.small.searchインスタンス
  - 750時間/月の使用

## 注意事項

- デモ用のアプリケーションなので、本番環境での使用は推奨しません
- セキュリティ対策（認証、認可など）は実装されていません
- エラーハンドリングは最小限の実装です
