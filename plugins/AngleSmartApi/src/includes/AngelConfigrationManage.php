<?php
namespace AngelBroking;

class AngelConfigrationManage
{
	public static function AngelConfigrationData()
	{
			
				
	 		$Configration = 

			    [
			        "root" => "https://apiconnect.angelbroking.com",
				    "login" => "https://smartapi.angelbroking.com/publisher-login",
				    "debug" => false,
				    "timeout" => 7000,
				    "user_login" => "/rest/auth/angelbroking/user/v1/loginByPassword",
				    "generate_token" => "/rest/auth/angelbroking/jwt/v1/generateTokens",
				    "get_profile" => "/rest/secure/angelbroking/user/v1/getProfile",
				    "logout"=> "/rest/secure/angelbroking/user/v1/logout",

				    "order_place" => "/rest/secure/angelbroking/order/v1/placeOrder",
				    // "order_status": "/order-service/rest/secure/angelbroking/order/v1/orderStatus",
				    "order_get_book" => "/rest/secure/angelbroking/order/v1/getOrderBook",
				    "order_modify" => "/rest/secure/angelbroking/order/v1/modifyOrder",
				    "order_cancel" => "/rest/secure/angelbroking/order/v1/cancelOrder",

				    "get_tradebook" => "/rest/secure/angelbroking/order/v1/getTradeBook",
				    "get_rms" => "/rest/secure/angelbroking/user/v1/getRMS",
				    "get_holding" => "/rest/secure/angelbroking/portfolio/v1/getHolding",
				    "get_position" => "/rest/secure/angelbroking/order/v1/getPosition",
				    "convert_position" => "/rest/secure/angelbroking/order/v1/convertPosition",

				    "create_rule" => "/rest/secure/angelbroking/gtt/v1/createRule",
				    "modify_rule" => "/rest/secure/angelbroking/gtt/v1/modifyRule",
				    "cancel_rule" => "/rest/secure/angelbroking/gtt/v1/cancelRule",
				    "rule_details" => "/rest/secure/angelbroking/gtt/v1/ruleDetails",
				    "rule_list" => "/rest/secure/angelbroking/gtt/v1/ruleList",
				
				    "candle_data"=>"/candle-data-service/rest/secure/angelbroking/historical/v1/getCandleData",
				    "ltp_data"=>"/rest/secure/angelbroking/order/v1/getLtpData",
				];

			return $Configration;	
	}		
}		
?>
