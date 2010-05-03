<?php

/**
 * Basset - A PHP browser sniffer
 * http://github.com/SirPepe/Basset
 *
 * Copyright (C) 2010 Peter Kröner
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Library General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Basset {

	/**
	 * @var string $ua User agent string
	 */
	public $ua = NULL;

	/**
	 * @var string $browser Browser type (e.g. "Firefox")
	 */
	public $browser = 'unknown';

	/**
	 * @var float $browserversion Browser version (e.g. "3.6")
	 */
	public $browser_version = 'unknown';

	/**
	 * @var string $platform OS type (e.g. "Windows")
	 */
	public $platform = 'unknown';

	/**
	 * @var float $platformversion OS version (e.g. "4.0")
	 */
	public $platform_version = 'unknown';

	/**
	 * @var string $platformtype Browser platform type (e.g. "Desktop")
	 */
	public $platform_type = 'unknown';

	/**
	 * @var array $mobile_agents List of keywords that may identify a mobile something
	 */
	public $mobile_agents = array(
		'android',
		'blackberry',
		'blazer',
		'handspring',
		'kyocera',
		'lg',
		'motorola',
		'nokia',
		'palm',
		'playstation portable',
		'samsung',
		'smartphone',
		'sonyericsson',
		'symbian',
		'wap',
		'htc',
		'midip'
	);


	/**
	 * __construct
	 * Class constructor. Sets the user agent var
	 * @return void
	 */
	public function __construct($ua = NULL){
		if($ua !== NULL){
			$this->ua = $ua;
		}
		else{
			$this->ua = $_SERVER['HTTP_USER_AGENT'];
		}
	}


	/**
	 * parse
	 * Parses the user agent
	 * @return void
	 */
	public function parse(){
		if($this->ua){
			$this->get_platform();
			// $this->get_browser();
		}
	}


	/**
	 * get_platform
	 * Tries to get the platform (os plus version number) from the ua string
	 * @return void
	 */
	public function get_platform(){
		// Windows
		if(preg_match('/windows/i', $this->ua)){
			$this->platform = 'windows';
			// Windows mobile or desktop?
			if(preg_match('/Windows CE/i',$this->ua)){
				$this->platform_type = 'mobile';
				$this->platform_version = '0';
			}
			else{
				$this->platform_type = 'desktop';
				$this->platform_version = $this->get_windows_version();
			}
		}
		// Mac
		elseif(preg_match('/mac/i', $this->ua)){
			$this->platform = 'mac';
			// Mobile or desktop?
			if(preg_match('/(iphone|ipod|mobile)/i', $this->ua)){
				// The iPad should count as "desktop"
				if(preg_match('/ipad/i', $this->ua)){
					$this->platform_type = 'desktop';
				}
				else{
					$this->platform_type = 'mobile';
				}
			}
			else{
				$this->platform_type = 'desktop';
			}
			$this->platform_version = $this->get_mac_version();
		}
		// Linux
		elseif(preg_match('/linux/i', $this->ua)){
			$this->platform = 'linux';
			// Mobile linux or desktop?
			if(preg_match('/(linux arm|android|kindle)/i', $this->ua)){
				$this->platform_type = 'mobile';
			}
			else{
				$this->platform_type = 'desktop';
			}
			$this->platform_version = '0';
		}
		// Unix
		elseif(preg_match('/(freebsd|openbsd|solaris|sunos)/i', $this->ua)){
			$this->platform = 'unix';
			$this->platform_type = 'desktop';
		}
		// Other mobile devices
		else{
			$mobile_agent = $this->detect_mobile_devices();
			if($mobile_agent !== false){
				// Set as platform
				$this->platform = $mobile_agent;
				$this->platform_version = 0;
				$this->platform_type = 'mobile';
				// Set as a browser if nothing better us available
				if($this->browser == 'unknown'){
					$this->browser = $mobile_agent;
					$this->browser_version = 0;
				}
			}
		}
	}


	/**
	 * detect_mobile_devices
	 * When all else fails, it is probably some strange mobile thingy
	 * @return $agent A mobile user agent (or false if nothing is found)
	 */
	public function detect_mobile_devices(){
		foreach($this->mobile_agents as $agent){
			if(preg_match('/'.$agent.'/i', $this->ua)){
				return $agent;
			}
		}
		return false;
	}


	/**
	 * get_windows_version
	 * Extracts the version number from a windows user agent string
	 * @return float The version number
	 */
	public function get_windows_version(){
		if(preg_match('/windows nt\s*([0-9\.]+)/i', $this->ua, $matches)){
			return $this->version_to_float($matches[1]);
		}
		elseif(preg_match('/windows\s*([0-9\.]+)/i', $this->ua, $matches)){
			return $this->version_to_float($matches[1]);
		}
		elseif(preg_match('/windows xp/i', $this->ua)){
			return 5.1;
		}
		elseif(preg_match('/windows 95/i', $this->ua)){
			return 4;
		}
		elseif(preg_match('/windows 98/i', $this->ua)){
			return 4.1;
		}
		elseif(preg_match('/windows me/i', $this->ua)){
			return 4.9;
		}
		else{
			return 0;
		}
	}


	/**
	 * get_mac_version
	 * Extracts the version number from an osx user agent string
	 * @return int The version number
	 */
	public function get_mac_version(){
		if(preg_match('/os x (?:[a-z]* )?([0-9_\.]+)/i', $this->ua, $matches)){
			return $this->version_to_float($matches[1]);
		}
		elseif(preg_match('/os x/i', $this->ua)){
			return 10;
		}
		else{
			return 'unknown';
		}
	}


	/**
	 * version_to_float
	 * Method to transform version numbers like 1.2.3 to a float of 1.23
	 * @param string $version Input version number
	 * @return float $version Output version number
	 */
	private function version_to_float($version){
		if(preg_match('/([-_\.])/', $version, $matches)){
			$delimeter = $matches[0];
			$version_array = explode($delimeter, strval($version));
			$version = $version_array[0];
			if(count($version_array) > 1){
				$version .= '.';
				for($i = 1; $i<count($version_array); $i++){
					$version .= $version_array[$i];
				}
			}
			return floatval($version);
		}
		else{
			return floatval($version);
		}
	}


}

?>
