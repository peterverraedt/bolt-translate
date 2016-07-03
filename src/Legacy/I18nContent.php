<?php

namespace Bolt\Extension\Verraedt\Translate\Legacy;

use Bolt\Legacy\Content as LegacyContent;
use Bolt\Storage\Entity\Content;
use Bolt\Library as Lib;

class I18nContent extends LegacyContent
{
    public function setValues(array $values)
    {
        // Since Bolt 1.4, we use 'ownerid' instead of 'username' in the DB tables. If we get an array that has an
        // empty 'ownerid', attempt to set it from the 'username'. In $this->setValue the user will be set, regardless
        // of ownerid is an 'id' or a 'username'.
        if (empty($values['ownerid']) && !empty($values['username'])) {
            $values['ownerid'] = $values['username'];
            unset($values['username']);
        }

        foreach ($values as $key => $value) {
            if ($key !== 'templatefields') {
                $this->setValue($key, $value, $values);
            }
        }

        // If default status is set in contentttype.
        if (empty($this->values['status']) && isset($this->contenttype['default_status'])) {
            $this->values['status'] = $this->contenttype['default_status'];
        }

        $serializedFieldTypes = [
            'geolocation',
            'imagelist',
            'image',
            'file',
            'filelist',
            'video',
            'select',
            'templateselect',
            'checkbox',
        ];
        // Check if the values need to be unserialized, and pre-processed.
        foreach ($this->values as $key => $value) {
            if ((in_array($this->fieldtype($key), $serializedFieldTypes)) || ($key == 'templatefields')) {
                if (!empty($value) && is_string($value) && (substr($value, 0, 2) == 'a:' || $value[0] === '[' || $value[0] === '{')) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }

                    if ($unserdata !== false) {
                        $this->values[$key] = $unserdata;
                    }
                }
            }

            if ($this->fieldtype($key) == 'video' && is_array($this->values[$key]) && !empty($this->values[$key]['url'])) {
                $video = $this->values[$key];

                // update the HTML, according to given width and height
                if (!empty($video['width']) && !empty($video['height'])) {
                    $video['html'] = preg_replace("/width=(['\"])([0-9]+)(['\"])/i", 'width=${1}' . $video['width'] . '${3}', $video['html']);
                    $video['html'] = preg_replace("/height=(['\"])([0-9]+)(['\"])/i", 'height=${1}' . $video['height'] . '${3}', $video['html']);
                }

                $responsiveclass = 'responsive-video';

                // See if it's widescreen or not.
                if (!empty($video['height']) && (($video['width'] / $video['height']) > 1.76)) {
                    $responsiveclass .= ' widescreen';
                }

                if (strpos($video['url'], 'vimeo') !== false) {
                    $responsiveclass .= ' vimeo';
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                // Mark them up as Twig_Markup.
                $video['html'] = new \Twig_Markup($video['html'], 'UTF-8');
                $video['responsive'] = new \Twig_Markup($video['responsive'], 'UTF-8');

                $this->values[$key] = $video;
            }

            if ($this->fieldtype($key) == 'date' || $this->fieldtype($key) == 'datetime') {
                if ($this->values[$key] === '') {
                    $this->values[$key] = null;
                }
            }
        }

        // Template fields need to be done last
        // As the template has to have been selected
        if ($this->isRootType) {
            if (empty($values['templatefields'])) {
                $this->setValue('templatefields', [], $values);
            } else {
                $this->setValue('templatefields', $values['templatefields'], $values);
            }
        }
    }

    public function setValue($key, $value, $values = null)
    {
        // Don't set templateFields if not a real contenttype
        if (($key === 'templatefields') && (!$this->isRootType)) {
            return;
        }

        // Check if the value need to be unserialized.
        if (is_string($value) && substr($value, 0, 2) === 'a:') {
            try {
                $unserdata = Lib::smartUnserialize($value);
            } catch (\Exception $e) {
                $unserdata = false;
            }

            if ($unserdata !== false) {
                $value = $unserdata;
            }
        }

        if ($key == 'id') {
            $this->id = $value;
        }

        // Set the user in the object.
        if ($key === 'ownerid' && !empty($value)) {
            $this->user = $this->app['users']->getUser($value);
        }

        // Only set values if they have are actually a field.
        $allowedcolumns = self::getBaseColumns();
        $allowedcolumns[] = 'taxonomy';
        if (!isset($this->contenttype['fields'][$key]) && !in_array($key, $allowedcolumns)) {
            return;
        }

        /**
         * This Block starts introducing new-style hydration into the legacy content object.
         * To do this we fetch the new field from the manager and hydrate a temporary entity.
         *
         * We don't return at this point so continue to let other transforms happen below so the
         * old behaviour will still happen where adjusted.
         */

        if (isset($this->contenttype['fields'][$key]['type']) && $this->app['storage.field_manager']->hasCustomHandler($this->contenttype['fields'][$key]['type'])) {
            $newFieldType = $this->app['storage.field_manager']->getFieldFor($this->contenttype['fields'][$key]['type']);
            $newFieldType->mapping['fieldname'] = $key;
            $entity = new Content();
            $entity->setContentType($this->contenttype['tablename']);
            $newFieldType->hydrate(is_null($values) ? [$key => $value, 'id' => $this->id] : $values, $entity);
            $value = $entity->$key;
        }

        if (in_array($key, ['datecreated', 'datechanged', 'datepublish', 'datedepublish'])) {
            if (!preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $value)) {
                // @todo Try better date-parsing, instead of just setting it to
                // 'now' (or 'the past' for datedepublish)
                if ($key == 'datedepublish') {
                    $value = null;
                } else {
                    $value = date('Y-m-d H:i:s');
                }
            }
        }

        if ($key === 'templatefields') {
            if ((is_string($value)) || (is_array($value))) {
                if (is_string($value)) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }
                } else {
                    $unserdata = $value;
                }

                if (is_array($unserdata)) {
                    $templateContent = new \Bolt\Legacy\Content($this->app, $this->getTemplateFieldsContentType(), [], false);
                    $value = $templateContent;
                    $this->populateTemplateFieldsContenttype($value);
                    $templateContent->setValues($unserdata);
                } else {
                    $value = null;
                }
            }
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date('Y-m-d H:i:s');
        }

        $this->values[$key] = $value;
    }
}
