<?php
/**
 * JBZoo Application
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Application
 * @license    GPL-2.0
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/JBZoo
 * @author     Denis Smetannikov <denis@jbzoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class ValidatorRenderer
 */
class ValidatorRenderer extends PositionRenderer
{

    /**
     * @var JBCartOrder
     */
    protected $_order = null;

    /**
     * @var JBModelConfig
     */
    protected $_jbconfig = null;

    /**
     * @param App  $app
     * @param null $path
     */
    public function __construct($app, $path = null)
    {
        parent::__construct($app, $path);

        $this->_jbconfig = JBModelConfig::model();
    }

    /**
     * @param string $position
     * @return bool
     */
    public function checkPosition($position)
    {
        foreach ($this->_getConfigPosition($position) as $index => $data) {
            if ($element = $this->_order->getValidatorElement($data['identifier'])) {

                $data['_layout']   = $this->_layout;
                $data['_position'] = $position;
                $data['_index']    = $index;

                if ($element->canAccess() && $element->hasValue()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $position
     * @param array  $args
     * @return string|void
     */
    public function renderPosition($position, $args = array())
    {
        // init vars
        $elements = array();
        $output   = array();
        $style    = isset($args['style']) ? $args['style'] : 'order.validator';
        $layout   = $this->_layout;

        // render elements
        foreach ($this->_getConfigPosition($position) as $index => $data) {
            if ($element = $this->_order->getValidatorElement($data['identifier'])) {

                if (!$element->canAccess() || !$element->hasValue()) {
                    continue;
                }

                $data['_layout']   = $this->_layout;
                $data['_position'] = $position;
                $data['_index']    = $index;

                // set params
                $params = array_merge((array)$data, $args);

                // check value
                $elements[] = compact('element', 'params');
            }
        }

        foreach ($elements as $i => $data) {

            $output[$i] = parent::render('element.' . $style, array(
                'element' => $data['element'],
                'params'  => array_merge(
                    array(
                        'first' => ($i == 0),
                        'last'  => ($i == count($elements) - 1)
                    ),
                    $data['params']
                ),
            ));
        }

        $this->_layout = $layout;

        if (isset($args['rowAttrs']) && is_array($args['rowAttrs'])) {
            $rowOutput = '';
            $_rowAttrs = array('class' => 'grid-row');
            $rowAttrs  = array_replace_recursive($_rowAttrs, $args['rowAttrs']);
            $column    = (isset($args['column'])) ? (int)$args['column'] : 3;

            $rowElements = array_chunk($output, $column);

            foreach ($rowElements as $elements) {
                $rowOutput .= '<div ' . $this->app->jbhtml->buildAttrs($rowAttrs) . '>';

                foreach ($elements as $element) {
                    $rowOutput .= $element;
                }

                $rowOutput .= '</div>';
            }

            return $rowOutput;
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * @param string $dir
     * @return array
     */
    public function getLayouts($dir)
    {
        // init vars
        $layoutList = array();
        $parts      = explode('.', $dir);
        $path       = implode('/', $parts);
        $xmlPath    = $this->_getPath($path . '/' . $this->_xml_file);

        // parse positions xml
        if ($xmlPath && $xml = simplexml_load_file($xmlPath)) {

            $layouts = $xml->xpath('positions[@layout]');

            foreach ($layouts as $layout) {

                $name = (string)$layout->attributes()->layout;

                $layoutList[$name] = $name;
            }

        }

        return $layoutList;
    }

    /**
     * @param $position
     * @return mixed
     */
    protected function _getConfigPosition($position)
    {
        return $this->_jbconfig->get($position, array(), 'cart.' . JBCart::CONFIG_VALIDATORS);
    }

    /**
     * @param string $layout
     * @param array  $args
     * @return string|void
     */
    public function render($layout, $args = array())
    {
        // set order
        $this->_order = isset($args['order']) ? $args['order'] : null;

        // init vars
        $render = true;
        $result = '';

        // trigger beforedisplay event
        if ($this->_order) {
            $this->app->jbevent->fire($this->_order, 'validator:beforedisplay', array(
                'render' => &$render,
                'html'   => &$result
            ));
        }

        // render layout
        if ($render) {
            $result .= parent::render($layout, $args);

            // trigger afterdisplay event
            if ($this->_order) {
                $this->app->jbevent->fire($this->_order, 'validator:afterdisplay', array(
                    'html' => &$result
                ));
            }
        }

        return $result;
    }

    /**
     * @param string $layout
     * @return JSONData
     */
    public function getLayoutParams($layout = 'default')
    {
        return $this->_jbconfig->get(JBCart::DEFAULT_POSITION, array(), 'cart.' . JBCart::CONFIG_VALIDATORS);
    }
}
