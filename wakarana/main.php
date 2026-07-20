<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Wakarana
*/
    define("WAKARANA_VERSION", "26.07-1");
/*
 *_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  LICENSE
 *
 *   このソフトウェアは、無権利創作宣言に基づき著作権放棄されています。
 *   営利・非営利を問わず、自由にご利用いただくことが可能です。
 *
 *    https://www.2pd.jp/license/
 *
*/

require_once(__DIR__."/wakarana_profile.php");
require_once(__DIR__."/wakarana_common.php");
require_once(__DIR__."/wakarana.php");


spl_autoload_register(function ($class_name) {
    if (str_starts_with($class_name, "wakarana_")) {
        $module_path = __DIR__."/".$class_name.".php";
        
        if (file_exists($module_path)) {
            require $module_path;
        }
    }
});


//以下、2027年6月以降のバージョンで削除
define("WAKARANA_STATUS_DISABLE", 0);
define("WAKARANA_STATUS_NORMAL", 1);
define("WAKARANA_STATUS_UNAPPROVED", -1);

define("WAKARANA_ORDER_USER_ID", "user_id");
define("WAKARANA_ORDER_USER_NAME", "user_name");
define("WAKARANA_ORDER_USER_CREATED", "user_created");

define("WAKARANA_BASE_ROLE", "__base__");
define("WAKARANA_ADMIN_ROLE", "__admin__");
