<?php
/**
 * Social API class
 *
 * MIT License. Copyright (c) 2018 Paulo Rodriguez
 * @author Paulo Rodriguez (xLaming)
 * @version 1.0 stable
 * @link https://paulao.me/social.php
 */
class SocialAPI
{
	/**
	 * Social links
	 * @var array
	 */
	protected $socials = [
		'instagram' => 'https://instagram.com/',
		'twitter'   => 'https://twitter.com/'
	];

	/**
	 * Get information from a Twitter profile
	 *
	 * @param  string $profile
	 * @return mixed
	 */
	public function getTwitter($profile)
	{
		if (empty($profile))
		{
			return false;
		}
		$getPage = $this->loadPage($this->socials['twitter'] . $profile);
		preg_match("/class=\"json-data\" value=\"(.*?)\"/is", $getPage, $final);
		if (empty($final) || empty($getPage))
		{
			return false;
		}
		$json    = json_decode($this->sanatizeJson($final[1]), true);
		$getUser = $json['profile_user'];
		$array   = [
			'user'      => $getUser['screen_name'],
			'name'      => $getUser['name'],
			'tweets'    => $getUser['statuses_count'],
			'followers' => $getUser['followers_count'],
			'following' => $getUser['friends_count'],
			'picture'   => $getUser['profile_image_url_https'],
			'banner'    => $getUser['profile_banner_url']
		];
		return $array;
	}

	/**
	 * Get information from a Instagram profile
	 *
	 * @param  string $profile
	 * @return mixed
	 */
	public function getInstagram($profile)
	{
		if (empty($profile))
		{
			return false;
		}
		$getPage = $this->loadPage($this->socials['instagram'] . $profile);
		preg_match("/sharedData = (.*?);/is", $getPage, $final);
		if (empty($final) || empty($getPage))
		{
			return false;
		}
		$json    = json_decode($final[1], true);
		$getUser = $json['entry_data']['ProfilePage'][0]['graphql']['user'];
		$array   = [
			'user'      => $getUser['username'],
			'name'      => $getUser['full_name'],
			'posts'     => $getUser['edge_owner_to_timeline_media']['count'],
			'followers' => $getUser['edge_followed_by']['count'],
			'following' => $getUser['edge_follow']['count'],
			'picture'   => $getUser['profile_pic_url_hd']
		];
		return $array;
	}

	/**
	 * Load external pages using cURL
	 *
	 * @param  string $url
	 * @return string
	 */
	private function loadPage($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	/**
	 * Sanatize bad Twitter JSON
	 *
	 * @param  string $json
	 * @return string
	 */
	private function sanatizeJson($json)
	{
		$json = htmlspecialchars_decode($json);
		$json = str_replace('&qquot;', '"', $json);
		return $json;
	}
}

/**
 * Initialize
 */
$api = new SocialAPI();

/**
 * Store content
 */
$json   = null;

/**
 * Get params by $_GET[]
 */
$user   = empty($_GET['u']) ? null : strtolower($_GET['u']);
$social = empty($_GET['s']) ? null : strtolower($_GET['s']);

/**
 * Switch, because it will have more social networkings to be added later
 */
switch ($social)
{
	case 'twitter':
		$json = $api->getTwitter($user);
		if (!$json)
		{
			$json['error'] = 'Profile not found';
		}
		break;

	case 'instagram':
		$json = $api->getInstagram($user);
		if (!$json)
		{
			$json['error'] = 'Profile not found';
		}
		break;

	default:
		$json['error'] = 'For now it is available for Twitter and Instagram';
		break;
}

/**
 * Set header JSON and print it
 */
header('Content-Type: application/json');
print json_encode($json, JSON_PRETTY_PRINT);
?>
