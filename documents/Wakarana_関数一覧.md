--------------------------------------------------------------------------------

  PHPユーザーマネージャライブラリ「Wakarana」設計案　ページ(3)

--------------------------------------------------------------------------------

# Wakarana 関数・定数まとめ


## common.php


### 定数

#### WAKARANA_VERSION
Wakaranaのバージョン番号文字列


### class wakarana_common
wakaranaクラスとwakarana_configクラスの親クラス。このクラスの関数は全てwakaranaクラスとwakarana_configクラスで使用できる。

#### wakarana_common::__construct($base_dir=NULL)
指定したフォルダにある設定ファイルをロードする。  
  
**$base_dir** : wakarana_config.iniのあるフォルダのパス。省略時はcommon.phpのあるフォルダを使用する。


#### wakarana_common::__get($name)
クラス内呼び出し用変数にクラス外からアクセスされた場合の処理。
ベースディレクトリ、設定ファイル変数値、DB接続については読み出しを許可する。  
  
**$name** : クラス内変数名。


#### ☆ wakarana_common::check_id_string($id, $length=60)
文字列に、ユーザーIDやロール名などの識別名として使用できない文字が含まれないかどうかを検査する。  
☆staticメソッド。  
  
**$id** : 検査する文字列  
**$length** : 文字列の長さの上限。検査する文字列がこれより長い場合は使用できない文字列とみなす。  
  
**返り値** ： 識別名として使用可能な文字列ならTRUEを、それ以外の場合はFALSEを返す。


#### ◆ wakarana_common::update_base_path($base_dir)
インスタンス変数として保持しているベースフォルダのパスを更新する。  
◆クラス内呼び出し専用であり、wakarana_common::__constructにより自動的に実行される。


#### ◆ wakarana_common::connect_db()
wakarana_config.iniの設定に基づき、データベースに接続する。  
◆クラス内呼び出し専用であり、wakaranaクラスとwakarana_configクラスはこの関数を自動的に実行する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ◆ wakarana_common::disconnect_db()
データベースとの接続を終了する。  
◆クラス内呼び出し専用。


#### wakarana_common::print_error($error_text)
エラーメッセージを出力する。  
ただし、wakarana_config.iniにおいてdisplay_errors=trueが設定されていなければ出力しない。  
wakaranaクラスインスタンスとwakarana_configクラスインスタンス、及び、wakaranaクラスインスタンスにより生成されたwakarana_userクラスインスタンスではエラー時にこの関数を実行する。  
  
**$error_text** : エラーメッセージ


#### wakarana_common::get_last_error_text()
wakarana_common::print_errorにて直近に入力されたエラーメッセージを返す。  
  
**返り値** ： エラーメッセージの文字列


#### wakarana_common::get_config_keys()
wakarana_config.iniの変数名一覧を取得する。  
  
**返り値** ： wakarana_config.iniの変数名一覧を配列で返す。


#### wakarana_common::get_config_value($key)
wakarana_config.iniの設定値を取得する。  
  
**$key** : wakarana_config.iniの変数名  
  
**返り値** ： 指定した変数名が存在すればその設定値、なければNULLを返す。


#### wakarana_common::get_custom_field_names()
ユーザーデータに追加可能なカスタムフィールド名一覧を取得する。  
  
**返り値** ： wakarana_custom_fields.jsonのキー一覧を配列で返す。


#### wakarana_common::get_custom_field_is_numeric($custom_field_name)
指定したカスタムフィールドが数値型かどうかを取得する。  
  
**$custom_field_name** : カスタムフィールド名  
  
**返り値** ： カスタムフィールド名がwakarana_custom_fields.jsonに存在する場合、数値型であればTRUE、文字列型ならFALSEを返す。カスタムフィールド名が存在しなければNULLを返す。


#### wakarana_common::get_custom_field_maximum_length($custom_field_name)
指定したカスタムフィールドに保存可能な最大文字数を取得する。  
  
**$custom_field_name** : カスタムフィールド名  
  
**返り値** ： カスタムフィールド名がwakarana_custom_fields.jsonに存在すればその最大文字数、存在しないかカスタムフィールドが数値型ならばNULLを返す。


#### wakarana_common::get_custom_field_records_per_user($custom_field_name)
指定したカスタムフィールドのユーザーあたりの上限件数を取得する。  
  
**$custom_field_name** : カスタムフィールド名  
  
**返り値** ： カスタムフィールド名がwakarana_custom_fields.jsonに存在すればその上限件数、存在しなければNULLを返す。


#### wakarana_common::get_custom_field_allow_nonunique_value($custom_field_name)
指定したカスタムフィールドで異なるユーザーが同一の値を持つことができるかを返す。  
  
**$custom_field_name** : カスタムフィールド名  
  
**返り値** ： カスタムフィールド名がwakarana_custom_fields.jsonに存在する場合、一意でない値を持てるならTRUE、持てないならFALSEを返す。カスタムフィールド名が存在しなければNULLを返す。


#### ◆ wakarana_common::load_email_domain_blacklist()
メールドメインブラックリストがインスタンス変数に読み込まれていなければ、ファイルから読み込む。  
◆クラス内呼び出し専用。


#### wakarana_common::check_email_domain($domain_name)
指定したドメインがメールドメインブラックリストに含まれないことを確認する。  
  
**$domain_name** : ドメイン名  
  
**返り値** ： ドメインがメールドメインブラックリストに含まれない場合はTRUE、含まれればFALSEを返す。


#### wakarana_common::get_email_domain_blacklist()
メールドメインブラックリストを配列で取得する。  
  
**返り値** ： メールドメインブラックリストのドメインを配列で返す。



## main.php


### 定数

#### WAKARANA_STATUS_DISABLE
「**0**」。wakarana_users.statusにおける停止中アカウント識別用。

#### WAKARANA_STATUS_NORMAL
「**1**」。wakarana_users.statusにおける有効なアカウント識別用。

#### WAKARANA_STATUS_UNAPPROVED
「**-1**」。wakarana_users.statusにおける未承認アカウント識別用。

#### WAKARANA_ORDER_USER_ID
「**user_id**」。ユーザー一覧の並び替え基準「ユーザーID」。

#### WAKARANA_ORDER_USER_NAME
「**user_name**」。ユーザー一覧の並び替え基準「ユーザー名」。

#### WAKARANA_ORDER_USER_CREATED
「**user_created**」。ユーザー一覧の並び替え基準「ユーザー作成日」。

#### WAKARANA_BASE_ROLE
「**\_\_BASE\_\_**」。ベースロールの識別名。

#### WAKARANA_ADMIN_ROLE
「**\_\_ADMIN\_\_**」。特権管理者ロールの識別名。

#### WAKARANA_BASE32_TABLE
Base32エンコード用の変換対応表。


### class wakarana
wakarana_commonの派生クラス。

#### wakarana::__construct($base_dir=NULL)
wakarana_common::__constructとwakarana_common::connect_dbを順に実行する。  
  
**$base_dir** : wakarana_config.iniのあるフォルダのパス。省略時はcommon.phpのあるフォルダを使用する。


#### ☆ wakarana::hash_password($user_id, $password)
パスワードのハッシュ値を生成する。  
☆staticメソッド。  
  
**$user_id** : ユーザーID  
**$password** : パスワード   
  
**返り値** ： ハッシュ化されたパスワードを返す。


#### ☆ wakarana::check_password_strength($password, $min_length=10)
パスワードの強度を確認する。  
☆staticメソッド。  
  
**$password** : パスワード   
**$min_length** : 強いパスワードとみなす最小の文字数  
  
**返り値** ： パスワードが指定した文字数以上かつ大文字・小文字・数字の全てを含むならTRUE、そうでないならFALSEを返す。


#### ◆ wakarana::new_wakarana_user($user_info)
Wakarana_userインスタンスを生成する。  
◆クラス内呼び出し専用。  
  
**$user_info** : ユーザー情報("user_id"(ユーザーID)、"user_name"(ユーザー名)、"password"(ハッシュ化されたパスワード)、"user_created"(アカウント作成日時)、"last_updated"(アカウント情報更新日時)、"last_access"(最終アクセス日時)、"status"(アカウントが使用可能か停止されているか)、"totp_key"(TOTPワンタイムパスワード生成キー))を格納した連想配列。


#### wakarana::get_user($user_id)
ユーザーIDで指定したユーザーのwakarana_userインスタンスを生成する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： ユーザーが存在する場合はwakarana_userクラスのインスタンス、存在しない場合はFALSEを返す。


#### wakarana::count_user()
ユーザーの総数を数える。  
  
**返り値** ： 登録されているユーザーの総数を返す。


#### wakarana::get_all_users($start=0, $limit=100, $order_by=WAKARANA_ORDER_USER_CREATED, $asc=TRUE)
全ユーザーの一覧を順に返す。  
  
**$start** ： 何番目のユーザーから取得するか(1番目なら「0」)  
**$limit** ： 何件まで取得するか  
**$order_by** : 並び替え基準。WAKARANA_ORDER_USER_CREATEDまたはWAKARANA_ORDER_USER_IDまたはWAKARANA_ORDER_USER_NAMEのいずれか。  
**$asc** : 昇順で取得する場合はTRUE、降順ならFALSE。  
  
**返り値** ： 成功した場合は、wakarana_userインスタンスを配列で返す。失敗した場合はFALSEを返す。


#### wakarana::add_user($user_id, $password, $user_name="", $status=WAKARANA_STATUS_NORMAL)
新しいユーザーを追加する。  
既に存在するユーザーIDを指定した場合はエラーとなる。  
  
**$user_id** ： 追加するユーザーのID。半角英数字及びアンダーバーが使用可能。  
**$password** ： 追加するユーザーのパスワード  
**$user_name** ： 追加するユーザーのハンドルネーム  
**$status** ： WAKARANA_STATUS_UNAPPROVEDを指定すると未承認ユーザー(ログイン不可)として作成することができる。  
  
**返り値** ： 成功した場合は追加したユーザーのwakarana_userインスタンスを返す。失敗した場合はFALSEを返す。


#### wakarana::get_role()
ロールのwakarana_roleインスタンスを生成する。  
  
**返り値** ： ロールが存在する場合はロールのwakarana_roleクラスのインスタンス、ロールが存在しない場合はFALSEを返す。


#### wakarana::get_all_roles()
存在するロールの一覧を取得する。  
  
**返り値** ： ロールのwakarana_roleインスタンスをロール名のアルファベット順に格納した配列を返す。ロールが存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana::add_role($role_id, $role_name, $role_description="")
ロールを新規作成する。  
  
**$role_id** ： ロールID。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
**$role_name** ： ロールの表示名  
**$role_description** : ロールについての説明文  
  
**返り値** ： 成功した場合は作成したロールのwakarana_roleインスタンスを、失敗した場合はFALSEを返す。


#### wakarana::get_permission()
権限のwakarana_permissionインスタンスを生成する。  
  
**返り値** ： 権限が存在する場合は権限のwakarana_permissionクラスのインスタンス、権限が存在しない場合はFALSEを返す。


#### wakarana::get_all_permissions()
存在する権限の一覧を取得する。  
  
**返り値** ： 権限のwakarana_permissionインスタンスを権限対象リソースIDのアルファベット順に格納した配列を返す。権限が存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana::get_all_permission_actions()
全ての権限とそこに存在する動作の一覧を取得する。  
  
**返り値** ： 権限対象リソースIDをキーとし、値として動作識別名の配列を持った連想配列を返す。権限が存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana::add_permission($resource_id, $permission_name, $classify_actions=TRUE, $permission_description="")
権限を新規作成する。  
権限は権限の表示名ではなく権限対象リソースのIDで識別される。  
権限対象リソースIDに「/」が含まれる場合、ユーザーが「/」以下を取り除いた権限対象リソースIDの権限を持っていれば、権限があるものとみなされる。  
  
**$resource_id** ： 権限対象リソースID。半角英数字及びアンダーバー、「/」が使用可能。アルファベット大文字は小文字に変換される。  
**$permission_name** ： 権限の表示名  
**$classify_actions** : 動作を識別するか。FALSEを指定すると動作「any」が自動作成される。  
**$permission_description** : 権限についての説明文  
  
**返り値** ： 成功した場合は作成した権限のwakarana_permissionインスタンスを、失敗した場合はFALSEを返す。


#### wakarana::get_permitted_value($permitted_value_id)
権限値のwakarana_permitted_valueインスタンスを生成する。  
  
**$permitted_value_id** ： 権限値ID  
  
**返り値** ： 権限値が存在する場合は権限値のwakarana_permitted_valueクラスのインスタンスを、権限値が存在しない場合は空配列を返す。


#### wakarana::get_all_permitted_values()
存在する権限値の一覧を取得する。  
  
**返り値** ： 権限値のwakarana_permitted_valueインスタンスを権限値IDのアルファベット順に格納した配列を返す。権限が存在しない場合は空配列を返す。


#### wakarana::add_permitted_value($permitted_value_id, $permitted_value_name, $permitted_value_description="")
権限値を新規作成する。  
  
**$permitted_value_id** ： 権限値ID。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
**$ppermitted_value_name** ： 権限値の表示名   
**$permitted_value_description** : 権限値についての説明文  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ☆ wakarana::create_random_password($length=14)
パスワードとして使用可能な文字列をランダムに生成する。  
☆staticメソッド。  
  
**$length** ： 生成するパスワードの文字数。3以上の数値を指定した場合、大文字・小文字・数字の全てを含むパスワードを生成する。  
  
**返り値** ： 英数字と記号(-と.)からなるランダムな文字列を返す。


#### ☆ wakarana::create_token()
トークンとして使用可能な文字列をランダムに生成する。  
☆staticメソッド。  
  
**返り値** ： 英数字と記号(-と_)からなるランダムな文字列を返す。


#### wakarana::delete_all_tokens()
データベースに存在する各種トークン(ログイントークン、ワンタイムトークン、メールアドレス確認トークン、パスワードリセット用トークン、2段階認証用一時トークン)を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::get_client_ip_address()
プロキシを除外してアクセス中のクライアント端末のIPアドレス文字列を取得し、それがIPアドレスとして正常な文字列であればそれを返す。  
  
**返り値** ： クライアント端末のIPアドレスをサニタイズして返す。IPアドレスの取得に失敗した場合は「0.0.0.0」を返す。


#### ☆ wakarana::get_client_environment()
アクセス中のクライアント端末の情報を連想配列で返す。  
☆staticメソッド。  
  
**返り値** ： キー"operating_system"(OS名)と"browser_name"(ブラウザ名)が含まれる連想配列。


#### wakarana::get_client_auth_logs($ip_address)
クライアントのIPアドレスからログイン試行履歴を新しい順に配列で取得する。  
  
**$ip_address** ： サニタイズ済みのIPアドレス  
  
**返り値** ： 成功した場合はそのIPアドレスの各試行履歴が格納された連想配列("user_id"(ユーザーID)、"succeeded"(正しいパスワードを入力したか否か)、"authenticate_datetime"(試行日時))を、配列に入れて返す。失敗した場合はFALSEを返す。


#### wakarana::check_client_auth_interval($ip_address, $unsucceeded_only=FALSE)
クライアントのIPアドレスが前回のログイン試行から次に試行できるようになるまでの期間を経過しているかを調べる。  
  
**$ip_address** ： サニタイズ済みのIPアドレス  
**$unsucceeded_only** : 失敗した試行のみを対象にする  
  
**返り値** ： wakarana_config.iniで指定した期間が経過していればTRUE、そうでない場合はFALSEを返す。


#### wakarana::delete_auth_logs($expire=-1)
指定した期間より前のログイン試行履歴を全て削除する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定した履歴の保持秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::authenticate($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合するが、トークンの生成と送信は行わない。  
内部的にログイン試行ログの参照と登録は実施する。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： 認証された場合はユーザーのwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、ユーザーIDが2段階認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::login($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合し、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： ログインが完了した場合はwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、ユーザーIDが2要素認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::authenticate_with_email_address($email_address, $password, $totp_pin=NULL)
ユーザーIDの代わりにメールアドレスを使用し、パスワードとTOTPコード(2要素認証を使用する場合)を照合する。トークンの生成と送信は行わない。  
内部的にログイン試行ログの参照と登録は実施する。  
  
wakarana_config.iniで同じメールアドレスを複数アカウントに使用できるよう設定している場合、この関数は使用できない。  
  
**$email_address** ： メールアドレス  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： 認証された場合はユーザーのwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、ユーザーIDが2段階認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::login_with_email_address($email_address, $password, $totp_pin=NULL)
ユーザーIDの代わりにメールアドレスを使用し、パスワードとTOTPコード(2要素認証を使用する場合)を照合、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
また、wakarana_config.iniで同じメールアドレスを複数アカウントに使用できるよう設定している場合、この関数は使用できない。  
  
**$email_address** ： メールアドレス  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： ログインが完了した場合はwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、ユーザーIDが2要素認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_tokens($expire=-1)
指定した経過時間より前に生成されたログイントークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したログイントークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::search_users_with_email_address($email_address)
メールアドレスからユーザーを逆引きする。  
  
**$email_address** ： 調べるメールアドレス  
  
**返り値** ： 指定したメールアドレスを登録しているユーザーがいれば、該当ユーザーらのwakarana_userインスタンスの配列、そうでない場合は空配列、エラーの場合は-1を返す。


#### wakarana::create_email_address_verification_code($email_address)
アカウント登録前の新規ユーザーに対してメールアドレス確認コードを生成し、データベースに登録する。  
この関数によりメールが送信されるわけではない。  
  
**$email_address** : コードの送信先メールアドレス  
  
**返り値** ： 成功した場合はメールアドレス確認コード文字列を、失敗した場合はFALSEを返す。同じメールアドレスでの複数のアカウント作成を許可しない設定の場合、既に使用されているメールアドレスならNULLを返す。


#### wakarana::email_address_verify($email_address, $code)
メールアドレスと確認コードを照合する。使用済みのメールアドレス確認コードは削除される。  
  
**$email_address** : コードが紐付けられたメールアドレス  
**$code** : メールアドレス確認コード  
  
**返り値** ： 認証された場合はwakarana_userインスタンス(既存ユーザーに対して生成された確認コードの場合)またはTRUE(新規ユーザーの場合)を返し、それ以外の場合はFALSEを返す。


#### wakarana::get_email_address_verification_code_expire($email_address, $code)
メールアドレス確認コードの有効期限を取得する。  
  
**$email_address** : コードが紐付けられたメールアドレス  
**$code** : メールアドレス確認コード  
  
**返り値** ： 有効な確認コードだった場合はYYYY-MM-DD hh:mm:ss形式の有効期限、それ以外の場合はFALSEを返す。


#### wakarana::delete_email_address_verification_codes($expire=-1)
指定した経過時間より前に生成されたメールアドレス確認コードを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したメールアドレス確認コードの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::check_invite_code($invite_code)
ユーザー招待コードを検証する。  
  
**$invite_code** : 招待コード文字列  
**$decrease_number** : TRUEを指定するか省略した場合、招待コードの残り回数が1つ減る。  
  
**返り値** ： 有効な招待コードだった場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana::get_invite_codes()
有効な全ての招待コードを取得する。  
  
**返り値** ： 成功した場合は、各招待コードの情報が格納された連想配列("invite_code"(招待コード本体)以外の項目はwakarana::get_invite_code_infoの返り値と同様)を発行日時の古い順に並べた配列を返す。失敗した場合はFALSEを返す。


#### wakarana::get_invite_code_info($invite_code)
ユーザー招待コードの情報(発行したユーザー、発行日時、有効期限、残り回数)を取得する。  
  
**$invite_code** : 招待コード文字列  
  
**返り値** ： 有効な招待コードだった場合は、ユーザー招待コードの情報を連想配列("user_id"(発行者のユーザーID)、"code_created"(YYYY-MM-DD hh:mm:ss形式の発行日時)、"code_expire"(YYYY-MM-DD hh:mm:ss形式の有効期限)、"remaining_number"(残り回数、無限の場合はNULL))で返す。それ以外の場合はFALSEを返す。


#### wakarana::delete_invite_code($invite_code=NULL)
ユーザー招待コードを削除する。  
  
**$invite_code** : 招待コード文字列。NULLを指定した場合は全ての招待コードを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::reset_password($token, $new_password)
パスワードリセット用トークンに紐付けられたアカウントのパスワードを再設定する。使用済みトークンは自動的に削除される。  
  
**$token** : パスワードリセット用トークン  
**$new_password** : 新しいパスワード  
**$delete_token** : TRUEの場合、使用済みのパスワードリセット用トークンを削除する。
  
**返り値** ： 成功した場合はトークンに紐付けられたユーザーのwakarana_userクラスのインスタンスを返し、それ以外の場合はFALSEを返す。


#### wakarana::get_password_reset_token_expire($token)
パスワードリセット用トークンの有効期限を取得する。  
  
**$token** : パスワードリセット用トークン  

**返り値** ： 有効な確認コードだった場合はYYYY-MM-DD hh:mm:ss形式の有効期限、それ以外の場合はFALSEを返す。


#### wakarana::delete_password_reset_tokens($expire=-1)
指定した経過時間より前に生成されたパスワードリセット用トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したパスワードリセット用トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::search_users_with_custom_field($custom_field_name, $custom_field_value)
カスタムフィールドの値からユーザーを逆引きする。  
  
**$custom_field_name** ： カスタムフィールド名  
**$custom_field_value** ： カスタムフィールド値  
  
**返り値** ： 指定した値と一致するカスタムフィールドの値を持つユーザーがいれば、該当ユーザーらのwakarana_userインスタンスの配列、そうでない場合は空配列、エラーの場合は-1を返す。


#### wakarana::delete_2sv_tokens($expire=-1)
指定した経過時間より前に生成された2段階認証用一時トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定した2段階認証用一時トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::totp_authenticate($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行う。  
  
**tmp_token** ： wakarana::authenticateにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： 認証された場合はユーザーのwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、それ以外の場合はFALSEを返す。


#### wakarana::totp_login($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行い、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**tmp_token** ： wakarana::loginにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： ログインが完了した場合はユーザーのwakarana_userインスタンス、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、それ以外の場合はFALSEを返す。


#### wakarana::check($token=NULL, $update_last_access=TRUE)
クライアント端末のcookieを参照し、正しくログインしているかどうかを照合する。  
  
**$token** ： 文字列を指定した場合、クライアント端末のcookie情報に関係なくその文字列をログイントークンとみなして照合処理を行う。  
**$update_last_access** : FALSEの場合、最終アクセス日時の更新を行わない。  
  
**返り値** ： 正しいログイントークンでログインしており、かつ、停止中のアカウントでない場合はそのトークンに対応するユーザーのwakarana_userインスタンス、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_token($token)
指定したログイントークンをデータベースから削除する。  
  
**$token** ： ログイントークン  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::logout()
接続中のクライアント端末が持つログイントークンをクライアント端末とデータベースの双方から削除し、ログアウト状態にする。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にのHTMLやHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**返り値** ： 成功した場合はTRUE、既にログアウトしている場合はNULL、失敗した場合はFALSEを返す。


#### wakarana::delete_one_time_tokens($expire=-1)
指定した経過時間より前に生成されたワンタイムトークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したトークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::totp_compare($totp_key, $totp_pin)
TOTPの規格に基づいて現在時刻のタイムスタンプで生成鍵とワンタイムコードを照合する。  
  
**$totp_key** ： TOTP生成鍵  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： 生成鍵に対して正しいTOTPコードだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### ◆☆ wakarana::bin_to_int($bin, $start, $length)
バイナリデータをビット単位で切り出し、整数に変換する。  
◆クラス内呼び出し専用。  
☆staticメソッド。  
  
**$bin** : バイナリデータを格納した文字列  
**$start** : 切り出し開始ビット  
**$length** : 切り出すビット数  
  
**返り値** ： 切り出したビット列を2進数として解釈した整数値を返す。


#### ◆☆ wakarana::int_to_bin($int, $digits_start)
整数値型のデータから2進数で8桁分のビットを切り出して1バイトのバイナリに変換する。  
◆クラス内呼び出し専用。  
☆staticメソッド。  
  
**$int** : 整数値型データ  
**$digits_start** : データを2進数に変換したときの下から何番目の位から切り出すか。  
  
**返り値** ： 1バイトの文字列に格納されたバイナリを返す。


#### ☆ wakarana::create_totp_key()
ランダムなTOTP生成鍵を作成する。この関数は作成したTOTP生成鍵をデータベースに保存しない。  
☆staticメソッド。  
  
**返り値** ： 16桁のTOTP生成鍵を返す。


#### ◆☆ wakarana::base32_decode($base32_str)
Base32エンコードされた文字列をバイナリにデコードする。  
◆クラス内呼び出し専用。  
☆staticメソッド。  
  
**$base32_str** : Base32エンコードされた文字列  
  
**返り値** ： バイナリデータを格納した文字列を返す。


#### ◆☆ wakarana::get_totp_pin($key_base32, $past_30s=0)
TOTP生成鍵と現在時刻からワンタイムコードを生成する。  
◆クラス内呼び出し専用。  
☆staticメソッド。  
  
**$key_base32** : TOTP生成鍵  
**$past_30s** : 負でない整数値。この値に30をかけた秒数過去のタイムスタンプを現在時刻とみなす。  
  
**返り値** ： ワンタイムコードを返す。


### class wakarana_user
ユーザーの情報を読み書きするためのクラス。1インスタンスごとに1ユーザーの情報が割り当てられる。

#### wakarana_user::__construct($wakarana, $user_info)
コンストラクタ。wakarana::get_user実行時に呼び出されるものであり、直接インスタンス化するべきではない。  
  
**$wakarana** : 呼び出し元のwakaranaインスタンス  
**$user_info** : ユーザー情報("user_id"(ユーザーID)、"user_name"(ユーザー名)、"password"(ハッシュ化されたパスワード)、"user_created"(アカウント作成日時)、"last_updated"(アカウント情報更新日時)、"last_access"(最終アクセス日時)、"status"(アカウントが使用可能か停止されているか)、"totp_key"(TOTPワンタイムパスワード生成キー))を格納した連想配列


#### ☆ wakarana_user::free($wakarana_user)
wakarana_userインスタンスをメモリから解放する。  
wakarana_userインスタンスはこの関数以外の方法(unsetや変数の上書き)では解放されない。  
☆staticメソッド。  
  
**$wakarana_user** : メモリから解放するwakarana_userインスタンス


#### wakarana_user::get_id()
ユーザーのIDを取得する。  
  
**返り値** ： ユーザーIDを返す。


#### wakarana_user::get_name()
ユーザー名を取得する。  
  
**返り値** ： ユーザー名が登録されていればユーザー名を、なければNULLを返す。


#### wakarana_user::check_password($password)
入力したパスワードとそのユーザーのパスワードが一致するかどうかを確認する。  
この関数は2段階認証を無視するため、ログイン認証に使用するべきではない。  
  
**$password** ： パスワード  
  
**返り値** ： 正しいパスワードだった場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana_user::get_primary_email_address()
ユーザーのメインメールアドレスを取得する。  
  
**返り値** ： メールアドレスが登録されていればメインメールアドレスを、なければNULLを返す。失敗した場合はFALSEを返す。


#### wakarana_user::get_email_addresses()
ユーザーのメールアドレス一覧を配列で取得する。  
  
**返り値** ： メールアドレスが登録されていればメールアドレスがアルファベット順に格納された配列を、なければ空配列を返す。失敗した場合はFALSEを返す。


#### wakarana_user::get_created()
ユーザーの登録日時を取得する。  
  
**返り値** ： YYYY-MM-DD hh:mm:ss形式の文字列。


#### wakarana_user::get_last_updated()
ユーザー情報の更新日時を取得する。  
  
**返り値** ： YYYY-MM-DD hh:mm:ss形式の文字列。


#### wakarana_user::get_last_access()
ユーザーの最終アクセス日時を取得する。  
  
**返り値** ： YYYY-MM-DD hh:mm:ss形式の文字列。


#### wakarana_user::get_status()
ユーザーの状態(アカウントが有効か停止されているか、等)を取得する。  
  
**返り値** ： WAKARANA_STATUS_NORMALまたはWAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED。


#### wakarana_user::get_totp_enabled()
ユーザーの2要素認証が有効になっているかを取得する。  
  
**返り値** ： 2要素認証が有効ならばTRUE、そうでない場合はFALSEを返す。


#### wakarana_user::get_value($custom_field_name)
ユーザーの指定したカスタムフィールドの単一の値を取得する。  
  
**$custom_field_name** ： カスタムフィールド名。2個以上の値の登録が可能なカスタムフィールドは指定できない。  
  
**返り値** ： 成功した場合はカスタムフィールドの値、失敗した場合はFALSEを返す。値が存在しない場合はNULLとみなす。


#### wakarana_user::get_values($custom_field_name)
ユーザーの指定したカスタムフィールドの値を配列で取得する。  
  
**$custom_field_name** ： カスタムフィールド名  
  
**返り値** ： カスタムフィールドの値を配列で返す。値が存在しない場合は空配列を、失敗した場合はFALSEを返す。


#### wakarana_user::set_password($password)
ユーザーのパスワードを変更する。  
  
**$password** ： パスワード  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_name($user_name)
ユーザー名を変更する。  
  
**$user_name** ： ユーザー名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::add_email_address($email_address)
ユーザーのメールアドレスを追加する。  
ユーザーに対して最初に登録されたメールアドレスは自動的にメインメールアドレスとなる。  
  
**$email_address** ： 新しいメールアドレス  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_primary_email_address($email_address)
登録済みのメールアドレスをユーザーのメインメールアドレスとして設定する。  
新たなメインメールアドレスが設定されることにより、元のメインメールアドレスはメインメールアドレスではなくなる。  
  
**$email_address** ： 登録済みのメールアドレス  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_email_address($email_address)
ユーザーのメールアドレスを削除する。  
この関数ではプライマリメールアドレスは削除できない。  
  
**$email_address** ： 削除するメールアドレス  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_all_email_addresses()
プライマリメールアドレスを含むユーザーのメールアドレスを全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_status($status)
ユーザーアカウントの状態(有効、停止、等)を切り替える。  
有効以外の状態を指定した場合、そのユーザーは自動的にログアウト状態となる。  
  
**$status** : WAKARANA_STATUS_NORMAL(有効)またはWAKARANA_STATUS_DISABLE(無効)またはWAKARANA_STATUS_UNAPPROVED(未承認)。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::enable_2_factor_auth($totp_key=NULL)
ユーザーのログイン時に2要素認証を要求するように設定する。  
  
**$totp_key** ： TOTP生成鍵。省略したときは自動で生成される。  
  
**返り値** ： 成功した場合はTOTP生成鍵、失敗した場合はFALSEを返す。


#### wakarana_user::disable_2_factor_auth()
ユーザーのログイン時に2要素認証を要求しないように設定する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_value($custom_field_name, $custom_field_value)
ユーザーの指定したカスタムフィールドの単一の値を設定する。  
  
**$custom_field_name** ： カスタムフィールド名。2個以上の値の登録が可能なカスタムフィールドは指定できない。  
**$custom_field_value** ： 値として保存する文字列または数値    
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::add_value($custom_field_name, $custom_field_value, $value_number=-1)
ユーザーの指定したカスタムフィールドに値を追加する。  
同一のユーザーに対して同じ値を複数追加することはできない。
  
**$custom_field_name** ： カスタムフィールド名  
**$custom_field_value** ： 値として保存する文字列または数値   
**$value_number** ： 並び順番号。既に値が存在する並び順番号を指定した場合、それより後の値の並び順番号を後ろにずらして新しい値を挿入する。既存の項目数+1よりも大きい値は使用できない。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::update_value($custom_field_name, $value_number, $custom_field_value)
ユーザーの指定したカスタムフィールドにおいて既に存在している値を上書きする。  
  
**$custom_field_name** ： カスタムフィールド名  
**$value_number** ： 上書きする値の並び順番号  
**$custom_field_value** ： 値として保存する文字列または数値  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_value($custom_field_name, $number_or_value=NULL)
ユーザーの指定したカスタムフィールドの値を削除する。  
削除された値より後の値の並び順番号は1つ前にずれる。  
  
**$custom_field_name** ： カスタムフィールド名  
**$number_or_value** ： 削除対象の並び順番号(1から順に付番されている整数値)または削除対象の値(文字列)。NULLを指定した場合はユーザーのそのカスタムフィールド値を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_all_values()
ユーザーの全てのカスタムフィールドの値を削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::get_roles()
ユーザーに割り当てられたロールの一覧を取得する。ただし、ベースロールは取得しない。  
  
**返り値** ： ロールのwakarana_roleインスタンスをロール名のアルファベット順に格納した配列を返す。ロールが存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana_user::add_role($role_id)
ユーザーにロールを付与する。既にそのユーザーに付与されているロールを指定するとエラーとなる。  
  
**$role_id** ： ロールID  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_role($role_id=NULL)
ユーザーからロールを剥奪する。ただし、ベースロールは剥奪できない。  
  
**$role_id** ： ロールID。NULLまたは省略した場合、全てのロールを剥奪する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::check_permission($resource_id, $action="any")
ユーザーに権限があるかを確認する。  
権限対象リソースIDに「/」が含まれる場合、「/」以下を取り除いた権限対象リソースIDの権限を持っていれば、権限があるものとみなす。  
  
**$resource_id** ： 権限対象リソースID  
**$action** ： 動作識別名  
  
**返り値** ： ユーザーが持つロールのいずれかに権限が割り当てられている場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana_user::get_permissions()
ユーザーに割り当てられた権限の一覧を取得する。  
  
**返り値** ： 権限対象リソースIDをキーとし、値として動作識別名の配列を持った連想配列を返す。権限が存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana_user::get_permitted_value($permitted_value_id)
ユーザーに割り当てられている最大の権限値を取得する。  
  
**$permitted_value_id** ： 権限値ID  
  
**返り値** ： ユーザーが持つ全ロールに割り当てられた権限値の中で最大のものを返す。どのロールにも権限値が割り当てられていない場合や失敗した場合はFALSEを返す。


#### wakarana_user::delete_all_tokens()
ユーザーの各種トークン(ログイントークン、ワンタイムトークン、メールアドレス確認コード、パスワードリセット用トークン、2段階認証用一時トークン)を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::get_auth_logs()
ユーザーのログイン試行履歴を新しい順に配列で取得する。  
  
**返り値** ： 成功した場合はそのユーザーの各試行履歴が格納された連想配列("succeeded"(認証に成功したか否か)、"authenticate_datetime"(試行日時), "ip_address"(IPアドレス))を、配列に入れて返す。失敗した場合はFALSEを返す。


#### wakarana_user::check_auth_interval($unsucceeded_only=FALSE)
ユーザーが前回のログイン試行から次に試行できるようになるまでの期間を経過しているかを調べる。  
  
**$unsucceeded_only** : 失敗した試行のみを対象にする  
  
**返り値** ： wakarana_config.iniで指定した期間が経過していればTRUE、そうでない場合はFALSEを返す。


#### wakarana_user::add_auth_log($succeeded)
ユーザーのログイン試行ログを登録する。  
  
**$succeeded** : ログインが成功した場合はTRUE、失敗した場合はFALSEを指定する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::delete_auth_logs()
ユーザーのログイン試行履歴を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::update_last_access($token=NULL)
現在の時刻をユーザーの最終アクセス日時として記録する。  
ログイントークンを指定した場合、そのトークンの最終アクセス日時も更新する。  
なお、ログイントークン発行処理とログアウト処理ではこの関数が自動的に実行される。
  
**$token** : ログイントークン文字列。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::get_login_tokens()
ユーザーに発行されている全てのログイントークンの情報を最終アクセス日時の新しい順に2次元配列で取得する。  
  
**返り値** ： 成功した場合は、そのユーザーに発行されている個々のログイントークンの情報が格納された連想配列("token"(トークン文字列の冒頭6文字)、"token_created"(トークンの生成日時)、"ip_address"(トークン生成時のクライアント端末のIPアドレス)、"operating_system"(トークン生成時のクライアント端末のOS名)、"browser_name"(トークン生成時のクライアント端末のブラウザ名)、"last_access"(そのトークンでの最終アクセス日時))を、配列に入れて返す。失敗した場合はFALSEを返す。  


#### wakarana_user::create_login_token()
ログイントークンを生成とデータベース登録処理を行うが、クライアント端末への送信は行わない。  
wakarana::loginとは別のトークン送信処理を実装する必要がある環境向け。  
  
**返り値** ： 成功した場合は登録されたログイントークン、失敗した場合はFALSEを返す。


#### wakarana_user::set_login_token()
ユーザーにトークンを割り当て、クライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::delete_login_token($abbreviated_token)
ユーザーの指定したログイントークンを削除する。  
  
**$abbreviated_token** ： 削除対象のトークンの冒頭6文字  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::delete_login_tokens()
ユーザーのログイントークンを全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。  


#### wakarana_user::authenticate($password, $totp_pin=NULL)
ユーザーに対するパスワードとTOTPコード(2要素認証を使用する場合)の照合を行う。  
トークンの生成と送信は行わないが、内部的にログイン試行ログの参照と登録は実施する。  
  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： 認証された場合はTRUE、ユーザーアカウントが停止中の場合はその状態値(WAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_UNAPPROVED)、ユーザーIDが2段階認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana_user::create_email_address_verification_code($email_address)
メールアドレス確認コードを生成し、ユーザーに割り当てる。  
前に同じユーザーに対して生成されたメールアドレス確認コードがデータベースに残っていた場合、古いコードは削除される。  
この関数によりメールが送信されるわけではない。  
  
**$email_address** : コードの送信先メールアドレス。  
  
**返り値** ： 成功した場合はメールアドレス確認コード、失敗した場合はFALSEを返す。


#### wakarana_user::delete_email_address_verification_code()
ユーザーに対して発行されているメールアドレス確認コードを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::create_invite_code($expire=NULL, $remaining_number=NULL)
ユーザー招待コードを生成する。  
  
**$expire** : 有効期限。YYYY-MM-DD hh:mm:ss形式の文字列。NULLを指定した場合は無限とみなす。  
**$remaining_number** : コードの使用可能回数。NULLを指定した場合は無限とみなす。  
  
**返り値** ： 成功した場合は招待コード文字列、失敗した場合はFALSEを返す。


#### wakarana_user::get_invite_codes()
ユーザーが発行した有効な全ての招待コードを取得する。  
  
**返り値** ： 成功した場合は、各招待コードの情報が格納された連想配列("invite_code"(招待コード本体)以外の項目はwakarana::get_invite_code_infoの返り値と同様)を発行日時の古い順に並べた配列を返す。失敗した場合はFALSEを返す。


#### wakarana_user::delete_invite_codes()
ユーザーが発行した全ての招待コードを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::create_password_reset_token()
ユーザーに対してパスワードリセット用トークンを発行する。  
  
**返り値** ： 成功した場合はパスワードリセット用トークン、失敗した場合はFALSEを返す。


#### wakarana_user::delete_password_reset_token()
ユーザーに対して発行されているパスワードリセット用トークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。  


#### wakarana_user::create_2sv_token()
ユーザーに対して2段階認証用の一時トークンを発行する。  
  
**返り値** ： 成功した場合は一時トークン、失敗した場合はFALSEを返す。


#### wakarana_user::delete_2sv_token()
ユーザーに対して発行されている2段階認証用の一時トークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。  


#### wakarana_user::create_one_time_token()
ユーザーが利用可能なワンタイムトークンを発行する。  
  
**返り値** ： 成功した場合はワンタイムトークンを、エラーの場合はFALSEを返す。


#### wakarana_user::check_one_time_token($token)
ワンタイムトークンを照合する。照合が終わったワンタイムトークンは自動的にデータベースから削除される。  
  
**$token** ： ワンタイムトークン  
  
**返り値** ： 正しいワンタイムトークンだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### wakarana_user::delete_one_time_tokens()
ユーザーのワンタイムトークンを全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::totp_check($totp_pin)
ユーザーに割り当てられたTOTP生成鍵とワンタイムコードを照合する。  
  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： ユーザーに割り当てられた生成鍵に対して正しいTOTPコードだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### wakarana_user::delete_user()
ユーザーアカウントをデータベースから完全に削除する。この関数を呼び出したwakarana_userインスタンスはそれ以降動作しなくなる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


### class wakarana_role
ロールの情報を読み書きするためのクラス。1インスタンスごとに1ロールの情報が割り当てられる。

#### wakarana_role::__construct($wakarana, $role_info)
コンストラクタ。wakarana::get_roleの実行時に呼び出されるものであり、直接インスタンス化するべきではない。  
  
**$wakarana** : 呼び出し元のwakaranaインスタンス  
**$role_info** : ロール情報("role_id"(ロールID)、"role_name"(ロール名)、"role_description"(ロールの説明文))を格納した連想配列


#### wakarana_role::get_name()
権限の表示名を取得する。  
  
**返り値** ： 権限の表示名を返す。


#### wakarana_role::get_description()
権限の説明文を取得する。  
  
**返り値** ： 権限についての説明文を返す。


#### wakarana_role::set_role_info($role_name, $role_description="")
ロールの情報を変更する。  
  
**$role_name** ： ロールの表示名  
**$role_description** : ロールについての説明文  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::get_users()
ロールが割り当てられているユーザーの一覧を取得する。  
  
**返り値** ： 成功した場合は、wakarana_userインスタンスをユーザーIDの順に配列で返す。失敗した場合はFALSEを返す。


#### wakarana_role::get_permissions()
ロールに割り当てられた権限の一覧を取得する。  
  
**返り値** ： 権限対象リソースIDをキーとし、値として動作識別名の配列を持った連想配列を返す。権限が存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana_role::check_permission($resource_id, $action="any")
ロールに権限が割り当てられているかを確認する。  
権限対象リソースIDに「/」が含まれる場合、「/」以下を取り除いた権限対象リソースIDの権限が存在すれば、権限が割り当てられているものとみなす。  
  
**$resource_id** ： 権限対象リソースID  
**$action** ： 動作識別名  
  
**返り値** ： ロールに権限が割り当てられている場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana_role::add_permission($resource_id, $action="any")
ロールに権限を追加する。  
特権管理者ロールは自動的に全ての権限が付与されるため、この関数は使用できない。  
  
**$resource_id** ： 権限対象リソースID  
**$action** ： 動作識別名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::remove_permission($resource_id, $action="any")
ロールから権限を剥奪する。  
特権管理者ロールでは権限が剥奪できないため、この関数は使用できない。  
  
**$resource_id** ： 権限対象リソースID  
**$action** ： 動作識別名。NULLを指定した場合は全ての動作を削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::remove_all_permissions()
ロールから全ての権限を剥奪する。  
特権管理者ロールでは権限が剥奪できないため、この関数は使用できない。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::get_permitted_values()
ロールに割り当てられている権限値の一覧を取得する。  
  
**返り値** ： 権限値IDをキー、権限値を値とする連想配列を返す。権限値が存在しない場合は空配列を返す。失敗した場合はFALSEを返す。


#### wakarana_role::get_permitted_value($permitted_value_id)
ロールに割り当てられている権限値を取得する。  
  
**$permitted_value_id** ： 権限値ID  
  
**返り値** ： 権限値が存在する場合は権限値を、存在しない場合はNULLを返す。失敗した場合はFALSEを返す。


#### wakarana_role::set_permitted_value($permitted_value_id, $permitted_value)
ロールの権限値を設定する。  
  
**$permitted_value_id** ： 権限値ID。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
**$permitted_value** : 権限値。TRUEは「1」、FALSEは「0」に変換される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::remove_permitted_value($permitted_value_id=NULL)
ロールの権限値を削除する。  
  
**$permitted_value_id** ： 権限値ID。NULLまたは省略した場合、全ての権限値を削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_role::delete_role()
ロールを完全に削除する。  
ただし、ベースロールと特権管理者ロールは使用できない。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


### class wakarana_permission
権限の情報を読み書きするためのクラス。1インスタンスごとに1権限の情報が割り当てられる。

#### wakarana_permission::__construct($wakarana, $permission_info)
コンストラクタ。wakarana::get_permissionの実行時に呼び出されるものであり、直接インスタンス化するべきではない。  
  
**$wakarana** : 呼び出し元のwakaranaインスタンス  
**$permission_info** : 権限情報("resource_id"(権限対象リソースID)、"permission_name"(権限名)、"permission_description"(権限の説明文))を格納した連想配列


#### wakarana_permission::get_name()
権限の表示名を取得する。  
  
**返り値** ： 権限の表示名を返す。


#### wakarana_permission::get_description()
権限の説明文を取得する。  
  
**返り値** ： 権限についての説明文を返す。


#### wakarana_permission::set_info($permission_name, $permission_description="")
権限の情報を変更する。  
  
**$permission_name** ： 権限の表示名  
**$permission_description** : 権限についての説明文  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_permission::get_actions()
権限に存在する動作の一覧を取得する。  
  
**返り値** ： 動作識別名を配列で返す。失敗した場合はFALSEを返す。


#### wakarana_permission::add_action($action)
権限で使用可能な動作を追加する。  
  
**$action** ： 動作識別名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_permission::delete_action($action=NULL)
権限で使用可能な動作を削除する。削除された動作は全てのロールから剥奪される。  
  
**$action** ： 動作識別名。NULLまたは省略した場合は全ての動作が削除される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_permission::delete_permission()
権限を全てのロールから剥奪して完全に削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


### class wakarana_permitted_value
権限値の情報を読み書きするためのクラス。1インスタンスごとに1権限値の情報が割り当てられる。

#### wakarana_permitted_value::__construct($wakarana, $permitted_value_info)
コンストラクタ。wakarana::get_permitted_valueの実行時に呼び出されるものであり、直接インスタンス化するべきではない。  
  
**$wakarana** : 呼び出し元のwakaranaインスタンス  
**$permitted_value_info** : 権限値情報("permitted_value_id"(権限値ID)、"permitted_value_name"(権限値名)、"permitted_value_description"(権限値の説明文))を格納した連想配列


#### wakarana_permitted_value::get_name()
権限値の表示名を取得する。  
  
**返り値** ： 権限値の表示名を返す。


#### wakarana_permitted_value::get_description()
権限値の説明文を取得する。  
  
**返り値** ： 権限値についての説明文を返す。


#### wakarana_permitted_value::set_info($permitted_value_name, $permitted_value_description="")
権限値の情報を変更する。  
  
**$permitted_value_name** ： 権限値の表示名  
**$permitted_value_description** : 権限値についての説明文  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_permitted_value::delete_permitted_value()
権限値を全てのロールから剥奪して完全に削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。



## config.php


### 定数

#### WAKARANA_CONFIG_ORIGINAL
wakarana_config.iniの既定値一覧。


### class wakarana_config
wakarana_commonの派生クラス。

#### wakarana_config::__construct($base_dir=NULL)
ベースフォルダに各種設定ファイル(wakarana_config.ini、wakarana_custom_fields.json、wakarana_email_domain_blacklist.conf)がなければ作成し、wakarana_common::__constructを実行する。  
  
**$base_dir** : wakarana_config.iniのある(または作成する)フォルダのパス。省略時はcommon.phpのあるフォルダを使用する。


#### ◆ wakarana_config::save()
現在の設定値でwakarana_config.iniを上書きする。  
◆クラス内呼び出し専用。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::set_config_value($key, $value, $save_now=TRUE)
wakarana_config.iniの設定値を変更する。  
  
**$key** : wakarana_config.iniの変数名  
**$value** : 設定する値  
**$save_now** : FALSEならwakarana_config.iniへの上書きは保留する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::reset_config()
wakarana_config.iniの設定値を全て既定値に戻す。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ◆ wakarana_config::save_custom_fields()
現在の設定値でcustom_fields.jsonを上書きする。  
◆クラス内呼び出し専用。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::add_custom_field($custom_field_name, $maximum_length=500, $records_per_user=1, $allow_nonunique_value=TRUE, $save_now=TRUE)
文字列型カスタムフィールドを追加する。  
既に存在するカスタムフィールド名を指定した場合はその設定を上書きする。  
  
**$custom_field_name** : カスタムフィールド名。半角英数字及びアンダーバーが使用可能。  
**$maximum_length** : 保存可能な最大文字数(500以下)  
**$records_per_user** : ユーザーあたりの上限件数(100以下)  
**$allow_nonunique_value** : 異なるユーザーが同一の値を持つことを認めるか  
**$save_now** : FALSEならcustom_fields.jsonへの上書きは保留する  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::add_custom_numerical_field($custom_field_name, $records_per_user=1, $allow_nonunique_value=TRUE, $save_now=TRUE)
数値型カスタムフィールドを追加する。  
既に存在するカスタムフィールド名を指定した場合はその設定を上書きする。  
  
**$custom_field_name** : カスタムフィールド名。半角英数字及びアンダーバーが使用可能。  
**$records_per_user** : ユーザーあたりの上限件数(100以下)  
**$allow_nonunique_value** : 異なるユーザーが同一の値を持つことを認めるか  
**$save_now** : FALSEならcustom_fields.jsonへの上書きは保留する  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::delete_custom_field($custom_field_name, $save_now=TRUE)
カスタムフィールドを削除する。  
この関数により既にデータベースに保存されている当該カスタムフィールドのデータが削除されるわけではない。  
  
**$custom_field_name** : カスタムフィールド名  
**$save_now** : FALSEならcustom_fields.jsonへの上書きは保留する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ◆ wakarana_config::save_email_domain_blacklist()
メールドメインブラックリストを上書き保存する。  
◆クラス内呼び出し専用。


### wakarana_config::add_email_domain_to_blacklist($damain_name)
ドメインをメールドメインブラックリストに追加する。  
  
**$domain_name** : ブラックリストに追加するドメイン名  
  
**返り値** ： 成功した場合はTRUE、既にブラックリストに登録されているドメインだった場合や失敗した場合はFALSEを返す。


### wakarana_config::remove_email_domain_from_blacklist($damain_name)
ドメインをメールドメインブラックリストから除外する。  
  
**$domain_name** : ブラックリストから除外するドメイン名  
  
**返り値** ： 成功した場合はTRUE、もとからブラックリストに登録されていないドメインだった場合や失敗した場合はFALSEを返す。


#### wakarana_config::setup_db()
データベースにテーブルを作成する。  
SQLiteを使用する設定の場合、データベースファイルの作成も行われる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。
