<?php

namespace onOffice\SDK\Cache;

interface onOfficeSDKCache
{
    public function __construct(array $options);

    /**
     * <pre><code>
     * $parameter1['actionid'] = $myactionid;
     * $parameter1['resourcetype'] = $myresourceType;
     * $parameter1['parameters'] = $myparameters;
     * $parameter1['resourceid'] = $myresourceId;
     * $parameter1['identifier'] = $myidentifier;
     *
     * $parameters = array($parameter1, $parameter...);
     * </code></pre>
     * see also <pre>ApiAction::getActionParameters()</pre> as these parameters are going to be used.
     *
     *
     * @return string|null must return null if not in cache or a string on success
     */
    public function getHttpResponseByParameterArray(array $parameters);

    /**
     * @param  array  $parameters requestParameters. See Above.
     * @param  string  $value the API response
     * @return bool true if written, false if not
     */
    public function write(array $parameters, $value);

    /**
     * Could be called by cron jobs
     * Leave blank if your cache system cleans up automatically
     */
    public function cleanup();

    /**
     * clear the entire cache
     */
    public function clearAll();
}
