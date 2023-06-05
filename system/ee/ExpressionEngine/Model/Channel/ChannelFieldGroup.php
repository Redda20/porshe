<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Collection;

/**
 * Channel Field Group Model
 */
class ChannelFieldGroup extends Model
{
    protected static $_primary_key = 'group_id';
    protected static $_table_name = 'field_groups';

    protected static $_hook_id = 'channel_field_group';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Channels' => array(
            'weak' => true,
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channels_channel_field_groups'
            ),
        ),
        'ChannelFields' => array(
            'weak' => true,
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelField',
            'pivot' => array(
                'table' => 'channel_field_groups_fields'
            )
        )
    );

    protected static $_validation_rules = array(
        'group_name' => 'required|unique|maxLength[50]|validateName',
        'short_name' => 'unique|maxLength[50]|alphaDash|validateNameIsNotReserved',
    );

    protected static $_events = array(
        'beforeValidate',
        'afterUpdate',
    );

    protected $group_id;
    protected $site_id;
    protected $group_name;
    protected $short_name;
    protected $group_description;

    /**
     * Convenience method to fix inflection
     */
    public function createChannelField($data)
    {
        return $this->createChannelFields($data);
    }

    public function validateName($key, $value, $params, $rule)
    {
        if (! preg_match("#^[a-zA-Z0-9_\-/\s]+$#i", (string) $value)) {
            return 'illegal_characters';
        }

        return true;
    }

    /**
     * The group short name must not intersect with Field names
     */
    public function validateUnique($key, $value, array $params = array())
    {
        $valid = parent::validateUnique($key, $value, $params);
        if ($valid === true) {
            if ($key == 'short_name') {
                $key = 'field_name';
            }
            // check channel fields
            $unique = $this->getModelFacade()
                ->get('ChannelField')
                ->filter($key, $value);

            foreach ($params as $field) {
                $unique->filter($field, $this->getProperty($field));
            }

            if ($unique->count() > 0) {
                return 'unique'; // lang key
            }

            // check member fields
            $unique = $this->getModelFacade()
                ->get('MemberField')
                ->filter('m_' . $key, $value);

            foreach ($params as $field) {
                $unique->filter('m_' . $field, $this->getProperty($field));
            }

            if ($unique->count() > 0) {
                return 'unique'; // lang key
            }

            return true;
        }

        return $valid;
    }

    /**
     * Validate the field name to avoid variable name collisions
     */
    public function validateNameIsNotReserved($key, $value, $params, $rule)
    {
        if (in_array($value, ee()->cp->invalid_custom_field_names())) {
            return lang('reserved_word');
        }

        return true;
    }

    /**
     * short_name did not exist prior to EE 7.3.0
     * we need a setter to set it automatically
     * if if was omitted from model make() call
     */
    public function onBeforeValidate()
    {
        if (empty($this->getProperty('short_name')) && !empty($this->getProperty('group_name'))) {
            $this->setProperty('short_name', substr('field_group_' . preg_replace('/\s+/', '_', strtolower($this->getProperty('group_name'))), 0, 50));
        }
    }

    public function onAfterUpdate($previous)
    {
        foreach ($this->Channels as $channel) {
            foreach ($channel->ChannelLayouts as $layout) {
                $layout->synchronize();
            }
        }
    }

    public function getAllChannels()
    {
        $channels = [];

        foreach ($this->Channels as $channel) {
            $channels[$channel->getId()] = $channel;
        }

        return new Collection($channels);
    }
}

// EOF
