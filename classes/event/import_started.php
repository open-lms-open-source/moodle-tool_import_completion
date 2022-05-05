<?php

namespace tool_import_completion\event;

class import_started extends \core\event\base
{

    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get name.
     * @return \lang_string|string
     */
    public static function get_name() {
        return get_string('eventimport_started', 'tool_import_completion');
    }

    /**
     * Get description.
     * @return \lang_string|string|null
     */
    public function get_description() {
        return get_string('eventimport_starteddesc', 'tool_import_completion', );
    }

}