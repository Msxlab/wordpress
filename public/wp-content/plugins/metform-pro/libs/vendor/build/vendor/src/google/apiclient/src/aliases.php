<?php

namespace MetFormProVendor;

if (\class_exists('MetFormProVendor\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['MetFormProVendor\\Google\\Client' => 'Google_Client', 'MetFormProVendor\\Google\\Service' => 'Google_Service', 'MetFormProVendor\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'MetFormProVendor\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'MetFormProVendor\\Google\\Model' => 'Google_Model', 'MetFormProVendor\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'MetFormProVendor\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'MetFormProVendor\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'MetFormProVendor\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'Google_AuthHandler_Guzzle5AuthHandler', 'MetFormProVendor\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'MetFormProVendor\\Google\\Http\\Batch' => 'Google_Http_Batch', 'MetFormProVendor\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'MetFormProVendor\\Google\\Http\\REST' => 'Google_Http_REST', 'MetFormProVendor\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'MetFormProVendor\\Google\\Task\\Exception' => 'Google_Task_Exception', 'MetFormProVendor\\Google\\Task\\Runner' => 'Google_Task_Runner', 'MetFormProVendor\\Google\\Collection' => 'Google_Collection', 'MetFormProVendor\\Google\\Service\\Exception' => 'Google_Service_Exception', 'MetFormProVendor\\Google\\Service\\Resource' => 'Google_Service_Resource', 'MetFormProVendor\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \MetFormProVendor\Google\Task\Composer
{
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
\class_alias('MetFormProVendor\\Google_Task_Composer', 'Google_Task_Composer', \false);
if (\false) {
    class Google_AccessToken_Revoke extends \MetFormProVendor\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \MetFormProVendor\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \MetFormProVendor\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \MetFormProVendor\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \MetFormProVendor\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \MetFormProVendor\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \MetFormProVendor\Google\Client
    {
    }
    class Google_Collection extends \MetFormProVendor\Google\Collection
    {
    }
    class Google_Exception extends \MetFormProVendor\Google\Exception
    {
    }
    class Google_Http_Batch extends \MetFormProVendor\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \MetFormProVendor\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \MetFormProVendor\Google\Http\REST
    {
    }
    class Google_Model extends \MetFormProVendor\Google\Model
    {
    }
    class Google_Service extends \MetFormProVendor\Google\Service
    {
    }
    class Google_Service_Exception extends \MetFormProVendor\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \MetFormProVendor\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \MetFormProVendor\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \MetFormProVendor\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \MetFormProVendor\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \MetFormProVendor\Google\Utils\UriTemplate
    {
    }
}
