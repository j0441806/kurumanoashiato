
# users

## 概要

ユーザー情報を管理する
TODO: Laravelの認証に必要なカラムに合わせる

| カラム名 | 型 | 必須 | 説明 |
|----------|-----|------|------|
| id | bigint | ○ | PK |
| name | varchar(100) | ○ | 名 |
| email | varchar(100) | ○ | メールアドレス |
| password | varchar(255) | ○ | パスワード |
| created_at | timestamp | ○ | 作成日時 |
| updated_at | timestamp | ○ | 更新日時 |


# cars

## 概要

車両情報を管理する

メモ: 
- メーカー・車種・グレードを別テーブルにするか検討（後続フェーズ）
- 初回は自由入力
- 区分は選択肢に変更


| カラム名 | 型 | 必須 | 説明 |
|----------|-----|------|------|
| id | bigint | ○ | PK |
| user_id | bigint | ○ | users.id |
| maker | varchar(100) | ○ | メーカー |
| model | varchar(100) | ○ | 車種 |
| grade | varchar(100) | × | グレード |
| category | integer | ○ | 区分 |
| station | varchar(255) | × | ステーション |
| memo | text | × | メモ |
| created_at | timestamp | ○ | 作成日時 |
| updated_at | timestamp | ○ | 更新日時 |


# drive_records

## 概要

ドライブ記録を管理する

メモ: 
- 立ち寄りポイントは初回はテキスト自由記述
- 道路の種類は選択肢？自由記述？
- 目的地は必須

| カラム名 | 型 | 必須 | 説明 |
|----------|-----|------|------|
| id | bigint | ○ | PK |
| car_id | bigint | ○ | cars.id |
| drive_date | date | ○ | ドライブ日 |
| departure | varchar(100) | ○ | 出発地 |
| destination | varchar(100) | ○ | 目的地 |
| spots | varchar(255) | × | 立ち寄りポイント |
| road_type | varchar(100) | × | 道路の種類 |
| distance | integer | ○ | 距離 |
| note_theme | text | × | 今回のテーマ |
| note_good_points | text | × | 良かったこと |
| note_issues | text | × | 課題 |
| note_next_action | text | × | 次回やること |
| note_free | text | × | 備考 |
| created_at | timestamp | ○ | 作成日時 |
| updated_at | timestamp | ○ | 更新日時 |


# photos

## 概要

写真を管理する
メモ：初回はcar_id、drive_record_idのどちらかのみがNULLになる制約を入れる

| カラム名 | 型 | 必須 | 説明 |
|----------|-----|------|------|
| id | bigint | ○ | PK |
| car_id | bigint | × | cars.id |
| drive_record_id | bigint | × | drive_records.id |
| file_path | varchar(255) | ○  | ファイルパス |
| note | text | × | 備考 |
| created_at | timestamp | ○ | 作成日時 |
| updated_at | timestamp | ○ | 更新日時 |


## メモ

### photos は中間テーブルにするべきか
- car_id または drive_record_id のどちらかが NULL
    - 必要があれば後からでも分離できるため、MVPでは中間テーブルを設けずにシンプルにする

photos について

中間テーブルも考えた
↓
現時点では写真の紐付け先が少ない
↓
まずはシンプルにする
↓
将来必要なら分離可能

### drive_records.user_id は持つべきか

```
users
 ↓
cars
 ↓
drive_records
```

- 車両がないのに運転記録があるという状態をこのアプリ上では不整合としたい
- drive_records.user_id を作成する場合、上記の整合性担保のための制御が必要になる
- そのコストを上回る実装上のメリットが特になければ、最初からデータ構造として持たない形にした

drive_records.user_id について

取得は楽になる
↓
しかし冗長になる
↓
整合性管理が必要になる
↓
今は持たない

「複雑なER図を書けること」ではなく、「なぜその構造にしたかを説明できること」に設計経験が表れている
「要件から必要な責務を切り出し、過剰設計と不足設計の間を探りながら構造を作れる人」

