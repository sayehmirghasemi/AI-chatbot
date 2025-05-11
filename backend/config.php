function generate_chat_response( $last_prompt, $conversation_history ) {

// لیست سوالات آفلاین (مثل دکمه‌ها)
$offline_qa = array(
    'چگونه پرداخت خود را انجام دهیم؟' => 'برای پرداخت، وارد حساب کاربری‌تون بشید و روی گزینه پرداخت کلیک کنید.',
    'آیا ارسال رایگانه؟' => 'بله، برای سفارش‌های بالای ۵۰۰ هزار تومان ارسال رایگان است.',
    'چند روزه محصول می‌رسه؟' => 'بین ۲ تا ۴ روز کاری محصول به دست‌تون می‌رسه.',
    'کد تخفیف میخوام!' => 'کد تخفیف فعلی: OFF25 برای خریدهای بالای ۳۰۰ هزار تومان 🎁'
);

if (array_key_exists($last_prompt, $offline_qa)) {
    return array(
        'success' => true,
        'message' => 'Offline answer',
        'result' => $offline_qa[$last_prompt]
    );
}

$api_url = 'https://api.openai.com/v1/chat/completions';
$api_key = ''; // 
$headers = [
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $api_key
];

array_unshift($conversation_history, [
    'role' => 'system',
    'content' => 'Answer questions only related to digital marketing, otherwise, say I dont know'
]);

$conversation_history[] = [
    'role' => 'user',
    'content' => $last_prompt
];

$body = [
    'model' => 'gpt-4o',
    'messages' => $conversation_history,
    'temperature' => 0.7
];

$args = [
    'method' => 'POST',
    'headers' => $headers,
    'body' => json_encode($body),
    'timeout' => 120
];

$response = wp_remote_request($api_url, $args);

if (is_wp_error($response)) {
    return array(
        'success' => false,
        'message' => $response->get_error_message(),
        'result' => ''
    );
} else {
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['choices'][0]['message']['content'])) {
        return array(
            'success' => false,
            'message' => 'API response error',
            'result' => ''
        );
    } else {
        $content = $data['choices'][0]['message']['content'];
        return array(
            'success' => true,
            'message' => 'Response generated',
            'result' => $content
        );
    }
}
}


function generate_dummy_response( $last_prompt, $conversation_history ) {
// Dummy static response
$dummy_response = array(
    'success' => true,
    'message' => 'done',
    'result' => "here is my reply"
);

// Return the dummy response as an associative array
return $dummy_response;
}

function handle_chat_bot_request( WP_REST_Request $request ) {
$last_prompt = $request->get_param('last_prompt');
$conversation_history = $request->get_param('conversation_history');

$response = generate_chat_response($last_prompt, $conversation_history);
return $response;
}

function load_chat_bot_base_configuration(WP_REST_Request $request) {
	// آدرس آواتارها
	$user_avatar_url = "https://chatbt.irannovin.co/wp-content/uploads/2025/03/icons8-user.gif";
	$bot_image_url = "https://chatbt.irannovin.co/wp-content/uploads/2025/03/icons8-operator.gif";

	// تعریف سوالات خاص آفلاین
	$offline_qa = array(
		'چگونه پرداخت خود را انجام دهیم؟' => 'برای پرداخت، وارد حساب کاربری‌تون بشید و روی گزینه پرداخت کلیک کنید.',
		'آیا ارسال رایگانه؟' => 'بله، برای سفارش‌های بالای ۵۰۰ هزار تومان ارسال رایگان است.',
		'چند روزه محصول می‌رسه؟' => 'بین ۲ تا ۴ روز کاری محصول به دست‌تون می‌رسه.',
		'کد تخفیف میخوام!' => 'کد تخفیف فعلی: OFF25 برای خریدهای بالای ۳۰۰ هزار تومان 🎁'
	);

	$common_buttons = array();
	foreach ($offline_qa as $question => $answer) {
		$common_buttons[] = array(
			'buttonText' => $question,
			'buttonPrompt' => $question,
			'buttonType' => 'offline'
		);
	}

	$response_data = array(
		'botStatus' => 1,
		'StartUpMessage' => "سلام! چجوری میتونم کمکت کنم؟",
		'fontSize' => '16',
		'userAvatarURL' => $user_avatar_url,
		'botImageURL' => $bot_image_url,
		'commonButtons' => $common_buttons,
		'offlineAnswers' => $offline_qa,
	);

	return new WP_REST_Response($response_data, 200);
}

add_action( 'rest_api_init', function () {
register_rest_route( 'myapi/v1', '/chat-bot/', array(
   'methods' => 'POST',
   'callback' => 'handle_chat_bot_request',
   'permission_callback' => '__return_true'
));
	
register_rest_route('myapi/v1', '/chat-bot-config', array(
    'methods' => 'GET',
    'callback' => 'load_chat_bot_base_configuration'
));
});