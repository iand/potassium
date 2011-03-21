<?php
// Potassium - a convenient PHP library for working with Kasabi

class Potassium {
  var $_apikey = '';
  var $_responses = array();
  
  function __construct($apikey, $httpconfig = array()) {
    $this->_apikey = $apikey;
    $this->_httpconfig = $httpconfig;
  }
  
  // Call an API and return the results in the most useful format possible
  // @return null if the request was not successful, an array or string otherwise
  function get($api_name_or_uri, $params = array(), $output='json') {
    if (strpos($api_name_or_uri, 'http://api.kasabi.com/api/') !== 0) {
      $api_name_or_uri = 'http://api.kasabi.com/api/' . $api_name_or_uri;
    }
    
    $uri = $api_name_or_uri . '?apikey=' . urlencode($this->_apikey) . '&output=' . urlencode($output);
    foreach ($params as $k => $v) {
      if (is_array($v)) $v = join(',', $v);
      $uri .= '&' . $k . '=' . urlencode($v);
    }
    $this->_do_get($uri);
    $response = $this->last_response();
    
    if ($response->responseCode < 200 || $response->responseCode >= 300) {
      return null;
    }
    
    if ($output == 'json') {
      // Automatically convert to array
      $results = json_decode($response->body, true);

      $format = $this->guess_format($results);
      if ($format == 'sparqlresults') {
        // Automatically simplify sparql results format
        return $this->simplify_sparql_results($results);
      }
      else {
        return $results;
      }
      
    }
    else {
      return $response->body;
    }
  }
  
  function last_response() {
    return $this->_responses[count($this->_responses) - 1];
  }
  
  function simplify_sparql_results($results) {
    $simple_results = array();
    $bindings = $results['results']['bindings'];
    for ($i = 0; $i < count($bindings); $i++) {
      $row = array();
      foreach ($bindings[$i] as $varname => $info) {
        $row[$varname] = $info['value'];
      }
      $simple_results[] = $row;
    }
    return $simple_results;
  }
  
  function guess_format($data) {
    if (isset($data['head']['vars']) && isset($data['results']['bindings'])) {
      return 'sparqlresults';
    }
    return 'unknown';
  }
  
  function _do_get($uri) {
    $response = http_parse_message(http_get($uri, $this->_httpconfig));
    $this->_responses[] = $response;
  }
}

if (!function_exists('http_get')) {
  function http_get($uri, $options) {
    $curl_handle = curl_init($uri);

    curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT,TRUE);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,1);

    /**
     * @see http://bugs.typo3.org/view.php?id=4292
     */
    if ( !(ini_get('open_basedir')) && ini_get('safe_mode') !== 'On') {
      curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
    }

    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT, isset($options['timeout']) ? $options['timeout'] : 600);
    curl_setopt($curl_handle, CURLOPT_HEADER, 1);

    $data = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $data;

  }
}

if (!function_exists('http_parse_message')) {
  function http_parse_message($response) {
    do
    {
      if ( strstr($response, "\r\n\r\n") == FALSE) {
        $response_headers = $response;
        $response = '';
      }
      else {
        list($response_headers,$response) = explode("\r\n\r\n",$response,2);
      }
      $response_header_lines = explode("\r\n",$response_headers);

      // first line of headers is the HTTP response code
      $http_response_line = array_shift($response_header_lines);
      if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@',$http_response_line,
      $matches)) {
        $response_code = $matches[1];
      }
      else
      {
        $response_code = "Error";
      }
    }
    while (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@',$response));

    $response_body = $response;

    // put the rest of the headers in an array
    $response_header_array = array();
    foreach ($response_header_lines as $header_line) {
      list($header,$value) = explode(': ',$header_line,2);
      $response_header_array[strtolower($header)] = $value;
    }

    $ret = new PotassiumResponse($response_code, $response_header_array, $response_body);
    return $ret;
  }
}

class PotassiumResponse {
  var $responseCode;
  var $headers;
  var $body;
  function __construct($response_code, $headers, $body) {
    $this->responseCode = $response_code;
    $this->headers = $headers;
    $this->body = $body;
  }
}
