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
config.iniをロードする。  
  
**$base_dir** : コンフィグファイルのあるフォルダのパス(common.phpのある階層からの相対パス)


#### ◆ wakarana_common::connect_db()
config.iniの設定に基づき、データベースに接続する。  
◆クラス内呼び出し専用であり、wakaranaクラスとwakarana_configクラスはこの関数を自動的に実行する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ◆ wakarana_common::disconnect_db()
データベースとの接続を終了する。  
◆クラス内呼び出し専用。


#### ◆ wakarana_common::print_error($error_text)
エラーメッセージを出力する。  
ただし、config.iniにおいてdisplay_errors=trueが設定されていなければ出力しない。  
◆クラス内呼び出し専用であり、wakaranaクラスとwakarana_configクラスではエラー時にこの関数を実行する。  
  
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

#### WAKARANA_STATUS_MAIL_UNCONFIRMED
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

#### wakarana::construct()
wakarana_common::__constructとwakarana_common::connect_dbを順に実行する。


#### wakarana::escape_id($id, $len=60)
ユーザーIDやロール名、権限名などの識別名として使用できない文字を除去する。  
  
**$id** : 変換前の文字列  
**$len** : 文字列の長さの上限  
  
**返り値** ： 変換後の文字列


#### wakarana::get_all_users($start=0, $limit=100, $order_by=WAKARANA_ORDER_USER_CREATED, $asc=TRUE)
全ユーザーの一覧を順に返す。  
  
**$start** ： 何番目のユーザーから取得するか(1番目なら「0」)  
**$limit** ： 何件まで取得するか  
**$order_by** : 並び替え基準。WAKARANA_ORDER_USER_CREATEDまたはWAKARANA_ORDER_USER_IDまたはWAKARANA_ORDER_USER_NAMEのいずれか。  
**$asc** : 昇順で取得する場合はTRUE、降順ならFALSE。  
  
**返り値** ： 成功した場合は、ユーザーIDを配列で返す。失敗した場合はFALSEを返す。


#### wakarana::mail_exists($mail_address)
あるメールアドレスを使用しているユーザーがいるかを調る。  
  
**$mail_address** ： 調べるメールアドレス  
  
**返り値** ： 指定したメールアドレスを登録しているユーザーがいればそのユーザーID、そうでない場合はFALSE、エラーの場合はNULLを返す。


#### wakarana::get_user_info($user_id)
ユーザーIDで指定したユーザーの各種情報を取得する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合は、ユーザーの各種情報を連想配列で返す。失敗した場合はFALSEを返す。


#### wakarana::add_user($user_id, $password, $user_name="", $mail_address=NULL, $status=WAKARANA_STATUS_NORMAL)
新しいユーザーを追加する。  
  
**$user_id** ： 追加するユーザーのID  
**$password** ： 追加するユーザーのパスワード  
**$user_name** ： 追加するユーザーのハンドルネーム  
**$mail_address** ： 追加するユーザーのメールアドレス。省略可。  
**$status** ： WAKARANA_STATUS_DISABLEを指定すると一時的に使用できないユーザーとして作成することができる。  
  
**返り値** ： 成功した場合はユーザーIDを返す。失敗した場合はFALSEを返す。


#### wakarana::change_user_data($user_id, $password=NULL, $user_name=NULL, $mail_address=NULL, $is_master=NULL, $status=NULL)
指定したIDのユーザーの各種情報を変更する。  
  
**引数については「add_user」と同様。** NULLを指定したものは変更しない。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_user($user_id)
ユーザーを削除する。  
  
**$user_id** ： 削除するユーザーのID
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::enable_2_factor_auth($user_id, $totp_key=NULL)
指定したIDのユーザーのログイン時に2段階認証を要求するように設定する。  
  
**$user_id** ： ユーザーID  
**$totp_key** ： TOTP生成鍵。省略したときは自動で生成される。  
  
**返り値** ： 成功した場合はTOTP生成鍵、失敗した場合はFALSEを返す。


#### wakarana::disable_2_factor_auth($user_id)
指定したIDのユーザーのログイン時に2段階認証を要求しないように設定する。    
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::get_roles($user_id=NULL)
ロール名の一覧を取得する。  
  
**$user_id** ： ユーザーID。NULLの場合はどのユーザーにも紐付けられていないものも含め、存在する全てのロールを返す。  
  
**返り値** ： ロール名をアルファベット順に格納した配列を返す。ロールが存在しない場合は空配列を返す。


#### wakarana::add_role($user_id, $role_name)
ユーザーにロールを付与する。  
  
**$user_id** ： ユーザーID  
**$role_name** ： ロール名  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::remove_role($user_id=NULL, $role_name=NULL)
ユーザーからロールを剥奪する。  
  
**$user_id** ： ユーザーID。NULLまたは省略した場合、全てのユーザーからロールを剥奪する。  
**$role_name** ： ロール名。NULLまたは省略した場合、全てのロールを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_role($role_name)
ロールを完全に削除する。  
  
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
  
**$role_name** ： ロール名  
**$permission_name** ： 権限名  
**$permission_value** : 設定値。TRUEは「1」、FALSEは「0」に変換される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::remove_permission_value($role_name=NULL, $permission_name=NULL)
指定したロールの権限を削除する。  
ロール名に定数WAKARANA_BASE_ROLEを指定するとベースロールを設定できる。  
  
**$role_name** ： ロール名。NULLまたは省略した場合、全てのロールから権限を剥奪する。  
**$permission_name** ： 権限名。NULLまたは省略した場合、全ての権限を削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::check_permission($user_id, $permission_name)
ユーザーに権限があるかを確認する。  
特権管理者ロール(WAKARANA_ADMIN_ROLE)を持つユーザーの場合、割り当てられていないか「0」が割り当てられている権限の権限値を全て「-1」とみなす。  
  
**$user_id** ： ユーザーID  
**$permission_name** ： そのユーザーが持っているかどうかを確認する権限の名前  
  
**返り値** ： そのユーザーが持っている各ロールの権限値のうち最大のものを返す。どのロールにも権限がない場合は「0」とみなす。


#### ◆ wakarana::create_token()
トークンとして使用可能な文字列をランダムに生成する。  
◆クラス内呼び出し専用。  
  
**返り値** ： 英数字と記号(-と_)からなるランダムな文字列を返す。


#### wakarana::delete_all_tokens($user_id=NULL)
指定したユーザーの全トークン(ログイントークン、ワンタイムトークン、メールアドレス確認トークン、2段階認証用一時トークン)を全て削除する。  
  
**$user_id** ： ユーザーID。NULLの場合は全ユーザーのトークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::get_user_environment()
アクセス中のクライアント端末の情報を連想配列で返す。  
  
**返り値** ： キー"os_name"(OS名)と"browser_name"(ブラウザ名)が含まれる連想配列。


#### wakarana::get_attempt_logs($user_id)
指定したユーザーのログイン試行履歴を取得する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はそのユーザーの各試行履歴が格納された連想配列("user_id"(ユーザーID)、"succeeded"(正しいパスワードを入力したか否か)、"attempt_datetime"(試行日時))を、配列に入れて返す。失敗した場合はFALSEを返す。


#### ◆ wakarana::check_attempt_interval($user_id)
前回のログイン試行から次に試行できるようになるまでの期間が経過しているかを調べる。  
◆クラス内呼び出し専用。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： config.iniで指定した期間が経過していればTRUE、そうでない場合はFALSEを返す。


#### ◆ wakarana::add_attempt_log($user_id, $succeeded)
ログイン試行ログを登録する。  
◆クラス内呼び出し専用。  
  
**$user_id** ： ユーザーID  
**$succeeded** : ログインが成功した場合はTRUE、失敗した場合はFALSEを指定する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_attempt_logs($user_id=NULL)
指定したユーザーのログイン試行履歴を全て削除する。  
  
**$user_id** ： ユーザーID。NULLを指定した場合は全ユーザーが対象となる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_old_attempt_logs($expire=-1)
指定した期間より前のログイン試行履歴を全て削除する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はconfig.iniで指定した履歴の保持秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::update_last_access($user_id)
現在の時刻を指定したユーザーの最終アクセス日時として記録する。  
ログイントークン発行処理とログアウト処理ではこの関数が自動的に実行される。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::check_password($user_id, $password)
ユーザーIDとそのユーザーのパスワードが正しいかどうかを確認する。  
この関数は2段階認証を無視するため、ログイン認証に使用するべきではない。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
  
**返り値** ： 正しいパスワードだった場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana::authenticate($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合するが、トークンの生成と送信は行わない。  
内部的にログイン試行ログの参照と登録は実施する。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： 認証された場合はTRUE、ユーザーIDが2段階認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::create_login_token($user_id)
ログイントークンを生成とデータベース登録処理を行うが、クライアント端末への送信は行わない。  
wakarana::loginとは別のトークン送信処理を実装する必要がある環境向け。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合は登録されたログイントークン、失敗した場合はFALSEを返す。


#### ◆ wakarana::set_login_token($user_id)
ユーザーにトークンを割り当て、クライアント端末に送信する。  
◆クラス内呼び出し専用。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::login($user_id, $password, $totp_pin=NULL)
ユーザーIDとパスワード、TOTPコード(2要素認証を使用する場合)を照合し、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**$user_id** ： ユーザーID  
**$password** ： パスワード  
**$totp_pin** ： 6桁のTOTPコード。2要素認証を使用しない場合と2要素認証の入力画面を分ける場合は省略。  
  
**返り値** ： ログインが完了した場合はTRUE、ユーザーIDが2要素認証の対象ユーザーでTOTPコードがNULLだった場合は仮トークン、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_tokens($user_id=NULL)
指定したユーザーのログイントークンを全て削除する。  
  
**$user_id** ： ユーザーID。NULLの場合は全ユーザーのログイントークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。  


#### wakarana::delete_old_login_tokens($expire=-1)
指定した経過時間より前に生成されたログイントークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はconfig.iniで指定したログイントークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::create_mail_confirmation_token($mail_address,$user_id=NULL)
メールアドレス確認トークンを生成し、データベースに登録する。  
この関数によりメールが送信されるわけではない。  
  
**$mail_address** : トークンの送信先メールアドレス。  
**$user_id** : ユーザーID。登録前の新規ユーザーに対してトークンを発行する場合はNULLを指定する。  
  
**返り値** ： 成功した場合はメールアドレス確認トークン、失敗した場合はFALSEを返す。


#### wakarana::mail_confirm($token, $delete_token=TRUE)
メールアドレス確認トークンを照合し、トークン生成時に紐付けられたメールアドレスとユーザーID(NULLの場合もある)を取得する。  
  
**$token** : メールアドレス確認トークン  
**$delete_token** : TRUEの場合、使用済みのメールアドレス確認トークンを削除する。  
  
**返り値** ： 認証された場合はキー"user_id"(ユーザーID)と"mail_address"(メールアドレス)が含まれる連想配列を返し、それ以外の場合はFALSEを返す。


#### wakarana::save_user_mail_address($token)
メールアドレス確認トークンを照合すし、トークンに紐付けられていたユーザーIDのユーザーのメールアドレスをトークンに紐付けられたメールアドレスで上書きする。  
  
**$token** : メールアドレス確認トークン  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_mail_confirmation_tokens($user_id=NULL)
指定したユーザーのメールアドレス確認トークンを全て削除する。  
  
**$user_id** ： ユーザーID。NULLの場合は全ユーザーのメールアドレス確認トークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_old_mail_confirmation_tokens($expire=-1)
指定した経過時間より前に生成されたメールアドレス確認トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はconfig.iniで指定したメールアドレス確認トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### ◆ wakarana::create_totp_temporary_token($user_id)
指定したユーザーに対して2段階認証用の一時トークンを発行する。  
◆クラス内呼び出し専用。  
  
**$user_id** : ユーザーID  
  
**返り値** ： 成功した場合は一時トークン、失敗した場合はFALSEを返す。


#### wakarana::delete_totp_tokens($user_id=NULL)
指定したユーザーの2段階認証用の一時トークンを全て削除する。  
  
**$user_id** ： ユーザーID。NULLの場合は全ユーザーの一時トークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_old_totp_tokens($expire=-1)
指定した経過時間より前に生成された2段階認証用一時トークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はconfig.iniで指定した2段階認証用一時トークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::totp_authenticate($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行う。  
  
**tmp_token** ： wakarana::authenticateにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： 認証された場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana::totp_login($tmp_token, $totp_pin)
ユーザーIDとパスワードが照合済みのユーザーに対してTOTPによる第2段階の認証を行い、正しければログイントークンを生成してクライアント端末に送信する。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**tmp_token** ： wakarana::loginにより発行される仮トークン  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： ログインが完了した場合はTRUE、それ以外の場合はFALSEを返す。


#### wakarana::check($token=NULL)
クライアント端末のcookieを参照し、正しくログインしているかどうかを照合する。  
  
**$token** ： 文字列を指定した場合、クライアント端末のcookieではなくその文字列をログイントークンとみなして照合処理を行う。  
  
**返り値** ： 正しいログイントークンでログインしていた場合はそのトークンに対応するユーザーID、それ以外の場合はFALSEを返す。


#### wakarana::delete_login_token($token)
指定したログイントークンをデータベースから削除する。  
  
**$token** ： ログイントークン  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::logout($delete_client_cookie=TRUE)
接続中のクライアント端末が持つログイントークンをクライアント端末とデータベースの双方から削除し、ログアウト状態にする。  
  
この関数はHTTPヘッダーの出力を伴うため、この関数より前にのHTMLやHTTPヘッダー以外の何らかの文字が出力されていた場合はエラーとなる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::create_one_time_token($user_id)
指定したユーザーが利用可能なワンタイムトークンを発行する。  
  
**$user_id** ： ユーザーID  
  
**返り値** ： 成功した場合はワンタイムトークンを、エラーの場合はFALSEを返す。


#### wakarana::check_one_time_token($user_id, $token)
ワンタイムトークンを照合する。照合が終わったワンタイムトークンは自動的にデータベースから削除される。  
  
**$user_id** ： ユーザーID  
**$token** ： ワンタイムトークン  
  
**返り値** ： 正しいワンタイムトークンだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### wakarana::delete_one_time_tokens($user_id=NULL)
指定したユーザーのワンタイムトークンを全て削除する。  
  
**$user_id** ： ユーザーID。NULLの場合は全ユーザーのワンタイムトークンを削除する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::delete_old_one_time_tokens($expire=-1)
指定した経過時間より前に生成されたワンタイムトークンを無効化する。  
  
**$expire** ： 経過時間の秒数。-1を指定した場合はconfig.iniで指定したトークンの有効秒数が代わりに使用される。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana::totp_compare($totp_key, $totp_pin)
TOTPの規格に基づいて生成鍵とワンタイムコードを照合する。  
  
**$totp_key** ： TOTP生成鍵  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： 生成鍵に対して正しいTOTPコードだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### wakarana::totp_check($user_id, $totp_pin)
ユーザーに割り当てられたTOTP生成鍵とワンタイムコードを照合する。  
  
**$user_id** ： ユーザーID  
**$totp_pin** ： 6桁のTOTPコード  
  
**返り値** ： ユーザーに割り当てられた生成鍵に対して正しいTOTPコードだった場合はTRUEを、それ以外の場合はFALSEを返す。


#### ◆ wakarana::bin_to_int($bin, $start, $length)
バイナリデータをビット単位で切り出し、整数に変換する。  
◆クラス内呼び出し専用。  
  
**$bin** : バイナリデータを格納した文字列  
**$start** : 切り出し開始ビット  
**$length** : 切り出すビット数  
  
**返り値** ： 切り出したビット列を2進数として解釈した整数値を返す。


#### ◆ wakarana::int_to_bin($int, $digits_start)
整数値型のデータから2進数で8桁分のビットを切り出して1バイトのバイナリに変換する。  
◆クラス内呼び出し専用。  
  
**$int** : 整数値型データ  
**$digits_start** : データを2進数に変換したときの下から何番目の位から切り出すか。  
  
**返り値** ： 1バイトの文字列に格納されたバイナリを返す。


#### wakarana::create_totp_key()
ランダムなTOTP生成鍵を作成する。この関数は作成したTOTP生成鍵をデータベースに保存しない。   
  
**返り値** ： 16桁のTOTP生成鍵を返す。


#### ◆ wakarana::base32_decode($base32_str)
Base32エンコードされた文字列をバイナリにデコードする。  
◆クラス内呼び出し専用。  
  
**$base32_str** : Base32エンコードされた文字列  
  
**返り値** ： バイナリデータを格納した文字列を返す。


#### ◆ wakarana::get_totp_pin($key_base32, $past_30s=0)
TOTP生成鍵と現在時刻からワンタイムコードを生成する。  
◆クラス内呼び出し専用。  
  
**$key_base32** : TOTP生成鍵  
**$past_30s** : 負でない整数値。この値に30をかけた秒数過去のタイムスタンプを現在時刻とみなす。  
  
**返り値** ： ワンタイムコードを返す。



## config.php


### 定数

#### WAKARANA_CONFIG_ORIGINAL
config.iniの既定値一覧。


### class wakarana_config
wakarana_commonの派生クラス。


#### ◆ wakarana_config::save()
現在の設定値でconfig.iniを上書きする。  
◆クラス内呼び出し専用。  


#### wakarana_config::set_config_value($key, $value, $save_now=TRUE)
config.iniの設定値を変更する。  
  
**$key** : config.iniの変数名  
**$value** : 設定する値  
**$save_now** : FALSEならconfig.iniへの上書きは保留する。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::reset_config()
config.iniの設定値を全て既定値に戻す。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。


#### wakarana_config::setup_db()
データベースにテーブルを作成する。  
SQLiteを使用する設定の場合、データベースファイルの作成も行われる。  
  
**返り値** ： 成功した場合はTRUE、失敗した場合はFALSEを返す。
