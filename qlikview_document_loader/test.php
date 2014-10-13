 <?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     13/10/14 11:21
 */
 libxml_disable_entity_loader(false);
phpinfo();
 exit();
                class NTLMSoapClient extends SoapClient
                {
                    public $serviceKey = null;
                    function __doRequest($request, $location, $action, $version, $one_way = 0)
                    {
                        $headers = array(
                            'Method: POST',
                            'Connection: Keep-Alive',
                            'User-Agent: PHP-SOAP-CURL',
                            'Content-Type: text/xml; charset=utf-8;',
                            'SOAPAction: '.$action
                        );
                        //Once you have a service key
                        if ($this->serviceKey)
                            $headers[] = 'X-Service-Key: ' . $this->serviceKey;
                        $this->__last_request_headers = $headers;
                        $ch = curl_init($location);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
                        curl_setopt($ch, CURLOPT_USERPWD, 'QVService:¸NwÔ%9­dR>Xp¶8h');
                        $response = curl_exec($ch);
                        return $response;
                    }
                    function __getLastRequestHeaders() {
                        return implode("\n", $this->__last_request_headers)."\n";
                    }
                }

$options = array(
    'exceptions'=>true,
    'trace'=>1
);
//Create SOAP connexion
$client = new NTLMSoapClient('http://qv.datlinq.com:4799/QMS/Service', $options);
//Retrieve a time limited service key
$client->serviceKey = $client->GetTimeLimitedServiceKey()->GetTimeLimitedServiceKeyResult;
//Retrieve server info
$qlikviewServerInfo = $client->GetServices(array('serviceTypes' => 'QlikViewServer'))->GetServicesResult;
echo 'QVS ID: ' . $qlikviewServerInfo->ServiceInfo->ID;

?>