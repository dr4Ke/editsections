<?php
/**
 * DokuWiki Plugin editsections (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Christophe Drevet <dr4ke@dr4ke.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_editsections_es extends DokuWiki_Action_Plugin {

	var $sections;

	function register(&$controller) {
		$controller->register_hook('PARSER_HANDLER_DONE', 'BEFORE', $this, 'rewrite_sections');
		$controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, '_addconf');
		$controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, '_editbutton');
	}

    function _addconf(&$event, $ags) {
        // add conf to JSINFO exported variable
        global $JSINFO;
        $JSINFO['es_order_type'] = $this->getConf('order_type');
    }

    function _editbutton(&$event, $param) {
	$order = $this->getConf('order_type');
//dbglog($event->data, 'edit section button data');
        if ($event->data['target'] === 'section') {
		$ind = $event->data['secid'];
		// Compute new values
		$last_ind = count($this->sections) - 1;
		$start = $this->sections[$ind]['start'];
		if ( $order === 0 ) {
			// flat editing
			$event->data['range'] = strval($start).'-'.strval($this->sections[$ind]['end']);
			$event->data['name'] = $this->sections[$ind]['name'];
		} elseif ( $order === 1 ) {
			// search end of nested section editing
			$end_ind = $ind;
			while ( ($end_ind + 1 <= $last_ind) and ($this->sections[$end_ind + 1]['level'] > $this->sections[$ind]['level']) ) {
				$end_ind++;
			}
			$event->data['range'] = strval($start).'-'.strval($this->sections[$end_ind]['end']);
			$event->data['name'] = $this->sections[$ind]['name'];
			if ($end_ind > $ind) {
				$event->data['name'] .= ' -> '.$this->sections[$end_ind]['name'];
			}
		} else {
		//dbglog('ERROR: section editing type unknown ('.$order.')');
		}
	//dbglog($event->data, 'edit section button data after');
        }
    }

	function rewrite_sections(&$event, $ags) {
		// get the instructions list from the handler
		$calls =& $event->data->calls;
		$edits = array();
		$order = $this->getConf('order_type');
		
		//dbglog($calls, 'calls before computing');
		// fake section inserted in first position in order to have an edit button before the first section
		$fakesection = array( array( 'header',				// header entry
		                              array ( ' ',			// juste a space, not shown in the final page
		                                      0,			// level 0 since this is not a real header
		                                      1),			// start : will be overwritten in the following loop
		                              1),				// start : will be overwritten in the following loop
		                      array ( 'section_open',			// section_open entry
		                              array(0),				// level
		                              1),				// start : will be overwritten in the following loop
		                      array ( 'section_close',			// section_close entry
		                              array(),				//
		                              1)				// end : will be overwritten in the following loop
		);
		$calls = array_merge($fakesection, $calls);
		// store all sections in a separate array to compute their start, end...
		$this->sections = array();
		$count = 0;
		foreach( $calls as $index => $value ) {
			if ($value[0] === 'header') {
				$count += 1;
				$this->sections[] = array( 'level' => $value[1][1],
				                     'start' => $value[2],
				                     'name' => $value[1][0],
				                     'header' => $index );
			//dbglog('Section '.($count - 1));
			//dbglog(' level '.$this->sections[$count - 1]['level']);
			//dbglog(' start '.$this->sections[$count - 1]['start']);
			//dbglog(' header index: '.$this->sections[$count - 1]['header']);
			}
			if ($value[0] === 'section_open') {
				if ($value[1][0] !== $this->sections[$count - 1]['level']) {
				//dbglog(' ERROR: section level different in section_open ('.$value[1][0].') and header ('.$this->sections[$count - 1]['level'].')');
				}
				if ($value[2] !== $this->sections[$count - 1]['start']) {
				//dbglog(' ERROR: section start different in section_open ('.$value[2].') and header ('.$this->sections[$count - 1]['start'].')');
				}
				$this->sections[$count - 1]['open'] = $index;
			//dbglog(' open index: '.$this->sections[$count - 1]['open']);
			}
			if ($value[0] === 'section_close') {
				$this->sections[$count - 1]['end'] = $value[2];
				$this->sections[$count - 1]['close'] = $index;
			//dbglog(' end of section: '.$this->sections[$count - 1]['end']);
			//dbglog(' close index: '.$this->sections[$count - 1]['close']);
			}
		}
	//dbglog($this->sections, 'sections');
		// Compute new values
		$h_ind = -1; // header index
		$o_ind = -1; // open section index
		$c_ind = -1; // close section index
		$last_ind = count($this->sections) - 1;
		foreach( $this->sections as $index => $value ) {
			// set values in preceding header
			if ( $h_ind >= 0 ) {
				// set start of section
				$calls[$h_ind][1][2] = $value['start'];
				$calls[$h_ind][2] = $value['start'];
			}
			// set values in preceding section_open
			if ( $o_ind >= 0 ) {
				// set start of section
				$calls[$o_ind][2] = $value['start'];
			}
			// set values in preceding section_close
			if ( $c_ind >= 0 ) {
				// set end of section
				$calls[$c_ind][2] = $value['end'];
			}
			// store indexes
			$h_ind = $value['header'];
			$o_ind = $value['open'];
			$c_ind = $value['close'];
		}
		// Now, set values for the last section start = end = last byte of the page
		// If not set, the last edit button disappear and the last section can't be edited
		// without editing entire page
		if ( $h_ind >= 0 ) {
			// set start of section
			$calls[$h_ind][1][2] = $this->sections[$last_ind][end];
			$calls[$h_ind][2] = $this->sections[$last_ind][end];
		}
		if ( $o_ind >= 0 ) {
			// set start of section
			$calls[$o_ind][2] = $this->sections[$last_ind][end];
		}
		if ( $c_ind >= 0 ) {
			// set end of section
			$calls[$c_ind][2] = $this->sections[$last_ind][end];
		}
		//dbglog($calls, 'calls after computing');
	}
}

// vim:ts=4:sw=4:et:enc=utf-8:
