<?php

namespace evandroaugusto\HttpClient;


class HttpClient
{
    protected $proxy;
    protected $ssl_verify_peer;


    public function __construct($proxy = false, $ssl = false)
    {
        $this->proxy = $proxy;
        $this->ssl_verify_peer = $ssl;
    }


    /**
     * HTTP - GET from an URL
     *
     * @param  string $url
     * @return string
     */
    public function get($url)
    {
        if (!$url) {
            throw new \Exception("You must set an URL to GET");
        }

        $curl = curl_init();
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verify_peer,
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($curl, $options);

        // check if need to use proxy
        if ($this->proxy) {
            $this->setCurlProxy($curl);
        }

        $response = curl_exec($curl);

        if ($error = curl_error($curl)) {
            throw new \Exception('CURL Error: ' . $error);
        }

        curl_close($curl);
        return $response;
    }


    /**
     * HTTP - POST to an url
     *
     * @param  string $url
     * @param  array $options
     * @return string
     */
    public function post($url, $attr)
    {
        if (!$url) {
            throw new \Exception("You must set an URL to POST");
        }

        $opt = [];
        
        // prepare POST header
        if (isset($attr['header']) && $attr['header']) {
            $opt['header'] = $attr['header'];
        }

        // prepare POST fields
        if (isset($attr['fields']) && $attr['fields']) {
            $attr = $attr['fields'];
        }

        // prepare post settings
        $curl = $this->_prepareRequest($url, $opt, $attr);

        if ($this->proxy) {
            $this->setCurlProxy($curl);
        }

        $response = curl_exec($curl);

        if ($error = curl_error($curl)) {
            throw new \Exception('CURL Error: ' . $error);
        }

        curl_close($curl);
        return $response;
    }

    /**
     * Perform HTTP requests
     *
     * @param  string $url
     * @param  array $attr
     * @return string
     */
    public function makeRequest($verb, $url, $options=[], $attr=[])
    {
        if (!$url) {
            throw new \Exception("You must set an URL to make an request");
        }

        // prepare post settings
        $curl = $this->_prepareRequest($url, $options, $attr, $verb);

        // check if need to use proxy
        if ($this->proxy) {
            $this->setCurlProxy($curl);
        }

        $response = curl_exec($curl);

        if ($error = curl_error($curl)) {
            throw new \Exception('CURL Error: ' . $error);
        }

        curl_close($curl);
        return $response;
    }


    //
    // PROTECTED/PRIVATE METHODS
    //

    /**
     * Adds proxy to cUrl
     * @param $ch
     */
    protected function setCurlProxy($curl)
    {
        $proxy_url = $this->proxy['host'];

        if (isset($this->proxy['port'])) {
            $proxy_url .= ':' . $this->proxy['port'];
        }

        curl_setopt($curl, CURLOPT_PROXY, $proxy_url);

        // use proxy credentials
        if (isset($this->proxy['username']) && isset($this->proxy['password'])) {
            curl_setopt(
                $curl,
                CURLOPT_PROXYUSERPWD,
                $this->proxy['username'] . ':' . $this->proxy['password']
            );
        }
    }

    /**
     * Prepare POST/PUT/PATCH settings
     */
    private function _prepareRequest($url, $opt, $attr, $verb = 'post')
    {
        $curl = curl_init();

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => false,
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verify_peer,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => null,
            CURLOPT_ENCODING       => ''
        );

        if (isset($opt['header']) && $opt['header']) {
            $options[CURLOPT_HTTPHEADER] = $opt['header'];
        } else {
            unset($options[CURLOPT_HTTPHEADER]);
        }

        $verb = strtoupper($verb);

        // prepare post settings (POST, DELETE, PATCH)
        switch ($verb) {
            case 'POST':
                $options[CURLOPT_POST] = true;

                if ($attr) {
                    $options[CURLOPT_POSTFIELDS] = $attr;
                }
                break;

            case 'DELETE':
            case 'PATCH':
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = $verb;

                if ($attr) {
                    $options[CURLOPT_POSTFIELDS] = $attr;
                }
                break;

            case 'GET':
                $querystring = null;
                
                if ($attr) {
                    $querystring = '?' . http_build_query($attr);
                }

                $options[CURLOPT_URL] = $url . $querystring;
                unset($options[CURLOPT_POSTFIELDS]);
                break;
        };

        curl_setopt_array($curl, $options);

        return $curl;
    }
}
