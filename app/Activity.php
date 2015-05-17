<?php namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Activity extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_log';

    protected $primaryKey = 'action_id';

    protected $hidden = [
    'action_type',
    'action_user_id',
    'actor_type',
    'developer',
    'details',
    'user_agent',
    'ip_address',
    ];

    public function getCreatedAtAttribute($value) {

        return \Carbon\Carbon::parse($value)->toIso8601String();
    }

    public function getUpdatedAtAttribute($value) {

        return \Carbon\Carbon::parse($value)->toIso8601String();
    }

    /**
     * Get the user that the activity belongs to.
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(config('auth.model'), 'user_id');
    }

    /**
     * Create an activity log entry.
     *
     * @param  mixed    $data
     * @return boolean
     */
    public static function log($data = [])
    {
        if (is_object($data))
            $data = (array) $data;

        if (is_string($data))
            $data = ['action' => $data];

        $activity = new static;

        if (config('log.auto_set_user_id'))
        {
            $user = \Auth::user();
            $activity->actor_user_id = isset($user->id) ? (int) $user->id : null;
        }

        if (isset($data['actor_user_id']) && is_string($data['actor_user_id']))
            $activity->actor_user_id = $data['actor_user_id'];

        $activity->actor_type   = isset($data['actor_type'])   ? $data['actor_type']   : null;
        $activity->action_user_id   = isset($data['action_user_id'])   ? $data['action_user_id']   : null;
        $activity->action_id   = isset($data['action_id'])   ? $data['action_id']   : null;
        $activity->action_type = isset($data['action_type']) ? $data['action_type'] : null;
        $activity->action       = isset($data['action'])      ? $data['action']      : null;
        $activity->description  = isset($data['description']) ? $data['description'] : null;
        $activity->details      = isset($data['details'])     ? $data['details']     : null;
        $activity->source      = isset($data['source'])     ? $data['source']     : 'Web';


        if (isset($data['deleted']) && $data['deleted'])
            $activity->action = "Delete";

        //set developer flag
        $activity->developer  = !is_null(Session::get('developer')) ? true : false;
        $activity->ip_address = Request::getClientIp();
        $activity->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'No UserAgent';
        $activity->save();

        return true;
    }

    /**
     * Get the name of the user.
     *
     * @return string
     */
    public function getName()
    {
        if ((bool) $this->developer)
            return config('log.developer_name');

        $user = $this->user;
        if (empty($user))
            return "Unknown User";

        if (!config('log.full_name_as_name'))
            return !is_null($user->username) ? $user->username : $user->name;

        if (config('log.full_name_last_name_first'))
            return $user->last_name.', '.$user->first_name;
        else
            return $user->first_name.' '.$user->last_name;
    }

    /**
     * Get a shortened version of the user agent with title text of the full user agent.
     *
     * @return string
     */
    public function getUserAgentPreview()
    {
        return substr($this->user_agent, 0, 42) . (strlen($this->user_agent) > 42 ? '<strong title="'.$this->user_agent.'">...</strong>' : '');
    }

    /**
     * Get the icon class name for the log entry's action.
     *
     * @return string
     */
    public function getIcon()
    {
        $actionIcons = config('log.action_icons');

        $actionFormatted = str_replace(' ', '_', trim(strtolower($this->action)));

        if (!is_null($this->action) && $this->action == "" || !isset($actionIcons[$actionFormatted]))
            return $actionIcons['x'];

        return $actionIcons[$actionFormatted];
    }

    /**
     * Get the markup for the log entry's icon.
     *
     * @return string
     */
    public function getIconMarkup()
    {
        $iconElement = config('log.action_icon.element');
        return '<'.$iconElement.' class="'.config('log.action_icon.class_prefix').$this->getIcon().'" title="'.$this->action.'"></'.$iconElement.'>';
    }

    /**
     * Get the URL for the log entry's content type if possible.
     *
     * @return string
     */
    public function getUrl()
    {
        $contentTypeSettings = config('log.content_types.'.snake_case($this->content_type));

        if (!is_array($contentTypeSettings) || !isset($contentTypeSettings['uri']))
            return null;

        $uri = str_replace(':id', $this->content_id, $contentTypeSettings['uri']);
        $url = URL::to($uri);

        $baseUrl = str_replace('https://', '', str_replace('http://', '', config('app.url')));

        // remove subdomain if one exists
        $url = preg_replace('/(http[s]?:\/\/)[A-Za-z0-9]*[\.]?('.str_replace('.', '\.', $baseUrl).')/', '${1}${2}', $url);

        // add subdomain if one is set
        if (isset($contentTypeSettings['subdomain']))
        {
            $subdomain = $contentTypeSettings['subdomain'];

            if (isset($subdomain) && $subdomain != "" && $subdomain !== false && !is_null($subdomain))
                $url = preg_replace('/(http[s]?:\/\/)('.str_replace('.', '\.', $baseUrl).')/', '${1}'.$subdomain.'.${2}', $url);
        }

        if (isset($contentTypeSettings['secure']) && $contentTypeSettings['secure'])
            $url = str_replace('http://', 'https://', $url);

        return $url;
    }

    /**
     * Get the linked description (if one is available). Otherwise, just get the description.
     *
     * @param  mixed    $class
     * @return string
     */
    public function getLinkedDescription($class = null)
    {
        if (is_null($this->getUrl()))
            return $this->description;

        return '<a href="'.$this->getUrl().'"'.(!is_null($class) ? ' class="'.$class.'"' : '').'>'.$this->description.'</a>' . "\n";
    }

    /**
     * Get the content item (if one is available).
     *
     * @param  boolean  $returnArray
     * @return object
     */
    public function getContentItem($returnArray = false)
    {
        $contentTypeSettings = config('log.content_types.'.snake_case($this->action_type));

        if (!is_array($contentTypeSettings) || !isset($contentTypeSettings['model']))
            return null;

        $item = call_user_func([$contentTypeSettings['model'], 'find'], (int) $this->action_id);

        if ($returnArray && is_object($item) && method_exists($item, 'toArray'))
            return $item->toArray();

        return $item;
    }

}