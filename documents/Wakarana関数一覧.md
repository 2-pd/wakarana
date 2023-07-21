--------------------------------------------------------------------------------

  PHPユーザーマネージャライブラリ「Wakarana」設計案　ページ(2)

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



## main.php


### 定数

#### WAKARANA_STATUS_DISABLE
「**0**」。wakarana_users.statusにおける停止中アカウント識別用。

#### WAKARANA_STATUS_NORMAL
「**1**」。wakarana_users.statusにおける有効なアカウント識別用。

#### WAKARANA_STATUS_EMAIL_UNVERIFIED
「**3**」。wakarana_users.statusにおけるメールアドレス未確認アカウント識別用。

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

#### wakarana::construct($base_dir=NULL)
wakarana_common::__constructとwakarana_common::connect_dbを順に実行する。  
  
**$base_dir** : wakarana_config.iniのあるフォルダのパス。省略時はcommon.phpのあるフォルダを使用する。


#### ☆ wakarana::check_id_string($id, $length=60)
文字列にユーザーIDやロール名、権限名などの識別名として使用できない文字が含まれないかどうかを検査する。  
☆staticメソッド。  
  
**$id** : 検査する文字列  
**$length** : 文字列の長さの上限。検査する文字列がこれより長い場合は使用できない文字列とみなす。   
  
**返り値** ： 識別名として使用可能な文字列ならTRUEを、それ以外の場合はFALSEを返す。


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


#### wakarana::get_user($user_id)
ユーザーIDで指定したユーザーのwakarana_userインスタンスを生成する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： ユーザーが存在する場合はwakarana_userクラスのインスタンス、存在しない場合はFALSEを返す。


#### wakarana::get_all_users($start=0, $limit=100, $order_by=WAKARANA_ORDER_USER_CREATED, $asc=TRUE)
全ユーザーの一覧を順に返す。  
  
**$start** ： 何番目のユーザーから取得するか(1番目なら「0」)  
**$limit** ： 何件まで取得するか  
**$order_by** : 並び替え基準。WAKARANA_ORDER_USER_CREATEDまたはWAKARANA_ORDER_USER_IDまたはWAKARANA_ORDER_USER_NAMEのいずれか。  
**$asc** : 昇順で取得する場合はTRUE、降順ならFALSE。  
  
**返り値** ： 成功した場合は、wakarana_userインスタンスを配列で返す。失敗した場合はFALSEを返す。


#### wakarana::add_user($user_id, $password, $user_name="", $email_address=NULL, $status=WAKARANA_STATUS_NORMAL)
新しいユーザーを追加する。  
既に存在するユーザーIDを指定した場合はエラーとなる。  
  
**$user_id** ： 追加するユーザーのID。半角英数字及びアンダーバーが使用可能。  
**$password** ： 追加するユーザーのパスワード  
**$user_name** ： 追加するユーザーのハンドルネーム  
**$email_address** ： 追加するユーザーのメールアドレス。省略可。  
**$status** ： WAKARANA_STATUS_DISABLEを指定すると一時的に使用できないユーザーとして作成することができる。  
  
**返り値** ： 成功した場合は追加したユーザーのwakarana_userインスタンスを返す。失敗した場合はFALSEを返す。


#### wakarana::get_roles()
存在するロール名の一覧を取得する。  
  
**返り値** ： ロール名をアルファベット順に格納した配列を返す。ロールが存在しない場合は空配列を返す。


#### wakarana::delete_role($role_name)
指定したロールを完全に削除する。ただし、ベースロールは削除できない。  
  
**$role_name** ： ロール名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::get_permission_values($role_name)
指定したロールに割り当てられている権限値の一覧を取得する。  
  
**$role_name** ： ロール名  
  
**返り値** ： 権限名をキーとする連想配列を返す。権限が存在しない場合は空配列を返す。


#### wakarana::set_permission_value($role_name, $permission_name, $permission_value=TRUE)
指定したロールの権限を追加・上書きする。  
存在しないロール名を指定した場合は新しくロールが作成される。  
ロール名に定数WAKARANA_BASE_ROLEを指定するとベースロールを設定できる。  
  
**$role_name** ： ロール名。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
**$permission_name** ： 権限名。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
**$permission_value** : 設定値。TRUEは「1」、FALSEは「0」に変換される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::remove_permission_value($role_name=NULL, $permission_name=NULL)
指定したロールの権限を剥奪する。  
ロール名に定数WAKARANA_BASE_ROLEを指定するとベースロールを初期化できる。  
  
**$role_name** ： ロール名。NULLまたは省略した場合、全てのロールから権限を剥奪する。  
**$permission_name** ： 権限名。NULLまたは省略した場合、全ての権限を剥奪する。  
  
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
データベースに存在する各種トークン(ログイントークン、ワンタイムトークン、メールアドレス確認トークン、2段階認証用一時トークン)を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ☆ wakarana::get_client_environment()
アクセス中のクライアント端末の情報を連想配列で返す。  
☆staticメソッド。  
  
**返り値** ： キー"os_name"(OS名)と"browser_name"(ブラウザ名)が含まれる連想配列。


#### wakarana::delete_attempt_logs($expire=-1)
指定した期間より前のログイン試行履歴を全て削除する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定した履歴の保持秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::authenticate($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合するが、トークンの生成と送信は行わない。  
内部的にログイン試行ログの参照と登録は実施する。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： 認証された場合はユーザーのwakarana_userインスタンス、ユーザーIDが2段階認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::login($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合し、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： ログインが完了した場合はwakarana_userインスタンス、ユーザーIDが2要素認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_tokens($expire=-1)
指定した経過時間より前に生成されたログイントークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したログイントークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::email_address_exists($email_address)
あるメールアドレスを使用しているユーザーがいるかを調る。  
  
**$email_address** ： 調べるメールアドレス  
  
**返り値** ： 指定したメールアドレスを登録しているユーザーがいればそのユーザーID、そうでない場合はFALSE、エラーの場合はNULLを返す。


#### wakarana::create_email_address_verification_token($email_address)
アカウント登録前の新規ユーザーに対してメールアドレス確認トークンを生成し、データベースに登録する。  
この関数によりメールが送信されるわけではない。  
  
**$email_address** : トークンの送信先メールアドレス。   
  
**返り値** ： 成功した場合はメールアドレス確認トークン、失敗した場合はFALSEを返す。


#### wakarana::email_address_verify($token, $delete_token=TRUE)
メールアドレス確認トークンを照合し、トークン生成時に紐付けられたメールアドレスとユーザーを取得する。 
トークンがアカウント登録前の新規ユーザーに対して生成されたものだった場合、メールアドレスのみを取得する。  
  
**$token** : メールアドレス確認トークン  
**$delete_token** : TRUEの場合、使用済みのメールアドレス確認トークンを削除する。  
  
**返り値** ： 認証された場合はキー"wakarana_user"(wakarana_userインスタンス)と"email_address"(メールアドレス)が含まれる連想配列を返し、それ以外の場合はFALSEを返す。


#### wakarana::delete_email_address_verification_tokens($expire=-1)
指定した経過時間より前に生成されたメールアドレス確認トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したメールアドレス確認トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::reset_password($token, $new_password)
パスワードリセット用トークンに紐付けられたアカウントのパスワードを再設定する。  
  
**$token** : パスワードリセット用トークン  
**$new_password** : 新しいパスワード  
  
**返り値** ： 成功した場合はトークンに紐付けられたユーザーのwakarana_userクラスのインスタンスを返し、それ以外の場合はFALSEを返す。


#### wakarana::delete_password_reset_tokens($expire=-1)
指定した経過時間より前に生成されたパスワードリセット用トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定したパスワードリセット用トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_totp_tokens($expire=-1)
指定した経過時間より前に生成された2段階認証用一時トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はwakarana_config.iniで指定した2段階認証用一時トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::totp_authenticate($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行う。  
  
**tmp_token** ： wakarana::authenticateにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： 認証された場合はユーザーのwakarana_userインスタンス、それ以外の場合はFALSEを返す。


#### wakarana::totp_login($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行い、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**tmp_token** ： wakarana::loginにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： ログインが完了した場合はユーザーのwakarana_userインスタンス、それ以外の場合はFALSEを返す。


#### wakarana::check($token=NULL)
クライアント端末のcookieを参照し、正しくログインしているかどうかを照合する。  
  
**$token** ： 文字列を指定した場合、クライアント端末のcookieではなくその文字列をログイントークンとみなして照合処理を行う。  
  
**返り値** ： 正しいログイントークンでログインしており、かつ、停止中のアカウントでない場合はそのトークンに対応するユーザーのwakarana_userインスタンス、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_token($token)
指定したログイントークンをデータベースから削除する。  
  
**$token** ： ログイントークン  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::logout($delete_client_cookie=TRUE)
接続中のクライアント端末が持つログイントークンをクライアント端末とデータベースの双方から削除し、ログアウト状態にする。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にのHTMLやHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


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
**$user_info** : ユーザー情報("user_id"(ユーザーID)、"user_name"(ユーザー名)、"password"(ハッシュ化されたパスワード)、"email_address"(メールアドレス)、"user_created"(アカウント作成日時)、"last_updated"(アカウント情報更新日時)、"last_access"(最終アクセス日時)、"status"(アカウントが使用可能か停止されているか)、"totp_key"(TOTPワンタイムパスワード生成キー))を格納した連想配列。


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


#### wakarana_user::get_email_address()
ユーザーのメールアドレスを取得する。  
  
**返り値** ： メールアドレスが登録されていればメールアドレスを、なければNULLを返す。


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
  
**返り値** ： WAKARANA_STATUS_NORMALまたはWAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_EMAIL_UNVERIFIED。


#### wakarana_user::get_totp_enabled()
ユーザーの2要素認証が有効になっているかを取得する。  
  
**返り値** ： 2要素認証が有効ならばTRUE、そうでない場合はFALSEを返す。


#### wakarana_user::set_password($password)
ユーザーのパスワードを変更する。  
  
**$password** ： パスワード  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_name($user_name)
ユーザー名を変更する。  
  
**$user_name** ： ユーザー名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_email_address($email_address)
ユーザーのメールアドレスを変更する。  
  
**$email_address** ： メールアドレス  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::set_status($status)
ユーザーアカウントの状態(有効、停止、等)を切り替える。  
  
**$status** : WAKARANA_STATUS_NORMALまたはWAKARANA_STATUS_DISABLEまたはWAKARANA_STATUS_EMAIL_UNVERIFIED。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::enable_2_factor_auth($totp_key=NULL)
ユーザーのログイン時に2段階認証を要求するように設定する。  
  
**$totp_key** ： TOTP生成鍵。省略したときは自動で生成される。  
  
**返り値** ： 成功した場合はTOTP生成鍵、失敗した場合はFALSEを返す。


#### wakarana_user::disable_2_factor_auth()
ユーザーのログイン時に2段階認証を要求しないように設定する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::get_roles()
ユーザーに割り当てられたロール名の一覧を取得する。ただし、ベースロールは取得しない。  
  
**返り値** ： ロール名をアルファベット順に格納した配列を返す。ロールが存在しない場合は空配列を返す。


#### wakarana_user::add_role($role_name)
ユーザーにロールを付与する。既にそのユーザーに付与されているロールを指定するとエラーとなる。  
  
**$user_id** ： ユーザーID  
**$role_name** ： ロール名。半角英数字及びアンダーバーが使用可能。アルファベット大文字は小文字に変換される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::remove_role($role_name=NULL)
ユーザーからロールを剥奪する。ただし、ベースロールは剥奪できない。  
  
**$role_name** ： ロール名。NULLまたは省略した場合、全てのロールを剥奪する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::check_permission($permission_name)
ユーザーに権限があるかを確認する。  
特権管理者ロール(WAKARANA_ADMIN_ROLE)を持つユーザーの場合、割り当てられていないか「0」が割り当てられている権限の権限値を全て「-1」とみなす。  
  
**$permission_name** ： 権限の名前  
  
**返り値** ： ユーザーが持っている各ロールの権限値のうち最大のものを返す。どのロールにも権限がない場合は「0」とみなす。失敗した場合はFALSEを返す。


#### wakarana_user::delete_all_tokens($user_id=NULL)
ユーザーの各種トークン(ログイントークン、ワンタイムトークン、メールアドレス確認トークン、2段階認証用一時トークン)を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::get_attempt_logs()
ユーザーのログイン試行履歴を取得する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はそのユーザーの各試行履歴が格納された連想配列("user_id"(ユーザーID)、"succeeded"(正しいパスワードを入力したか否か)、"attempt_datetime"(試行日時))を、配列に入れて返す。失敗した場合はFALSEを返す。


#### wakarana_user::check_attempt_interval()
ユーザーが前回のログイン試行から次に試行できるようになるまでの期間が経過しているかを調べる。  
  
**返り値** ： wakarana_config.iniで指定した期間が経過していればTRUE、そうでない場合はFALSEを返す。


#### wakarana_user::add_attempt_log($succeeded)
ユーザーのログイン試行ログを登録する。  
  
**$succeeded** : ログインが成功した場合はTRUE、失敗した場合はFALSEを指定する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::delete_attempt_logs()
ユーザーのログイン試行履歴を全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::update_last_access()
現在の時刻をユーザーの最終アクセス日時として記録する。  
ログイントークン発行処理とログアウト処理ではこの関数が自動的に実行される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::create_login_token()
ログイントークンを生成とデータベース登録処理を行うが、クライアント端末への送信は行わない。  
wakarana::loginとは別のトークン送信処理を実装する必要がある環境向け。  
  
**返り値** ： 成功した場合は登録されたログイントークン、失敗した場合はFALSEを返す。


#### wakarana_user::set_login_token()
ユーザーにトークンを割り当て、クライアント端末に送信する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_user::delete_login_tokens()
ユーザーのログイントークンを全て削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。  


#### wakarana_user::create_email_address_verification_token($email_address)
メールアドレス確認トークンを生成し、ユーザーに割り当てる。  
前に同じユーザーに対して生成されたメールアドレス確認トークンがデータベースに残っていた場合、古いトークンは削除される。  
この関数によりメールが送信されるわけではない。  
  
**$email_address** : トークンの送信先メールアドレス。    
  
**返り値** ： 成功した場合はメールアドレス確認トークン、失敗した場合はFALSEを返す。


#### wakarana_user::create_password_reset_token()
ユーザーに対してパスワードリセット用トークンを発行する。  
  
**返り値** ： 成功した場合はパスワードリセット用トークン、失敗した場合はFALSEを返す。


#### wakarana_user::create_totp_temporary_token()
ユーザーに対して2段階認証用の一時トークンを発行する。  
  
**返り値** ： 成功した場合は一時トークン、失敗した場合はFALSEを返す。


#### wakarana_user::create_one_time_token()
ユーザーが利用可能なワンタイムトークンを発行する。  
  
**$user_id** ： ユーザーID  
  
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


#### wakarana_user::delete()
ユーザーアカウントをデータベースから完全に削除する。このとき、現在のwakarana_userインスタンスも開放される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。



## config.php


### 定数

#### WAKARANA_CONFIG_ORIGINAL
wakarana_config.iniの既定値一覧。


### class wakarana_config
wakarana_commonの派生クラス。


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


#### wakarana_config::setup_db()
データベースにテーブルを作成する。  
SQLiteを使用する設定の場合、データベースファイルの作成も行われる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。
