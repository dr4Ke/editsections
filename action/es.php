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
        // FIXME: Insert plugin name
dbglog($event->data, '_editbutton call');
        if ($event->data['target'] !== 'plugin_editsections') {
            return;
        }

        // FIXME: Add lang field to your lang files
        //$event->data['name'] = $this->getLang('sectioneditname');
        $event->data['name'] = 'sectioneditname';
    }

	function rewrite_sections(&$event, $ags) {
		// get the instructions list from the handler
		$calls =& $event->data->calls;
		$edits = array();
		$order = $this->getConf('order_type');
		
		dbglog($calls);
		// fake section inserted in first position in order to have an edit button before the first section
		$fakesection = array( array( 'header',				// header entry
		                              array ( ' ',			// text
		                                      0,			// level
		                                      1),			// start
		                              1),				// start
		                      array ( 'section_open',			// section_open entry
		                              array(1),				// level
		                              1),				// start
		                      array ( 'section_close',			// section_close entry
		                              array(),				//
		                              33)				// end
		);
		$calls = array_merge($fakesection, $calls);
		// indexes of preceding section initialized to the fake section
		$header_index = 0;
		$s_open_index = 1;
		$s_close_index = 2;
		foreach( $calls as $index => $value ) {
			if ($index < 3) {
				// skip fake section
				continue;
			}
			if ($value[0] === 'header') {
				$calls[$header_index][1][2] = $value[1][2];
				$calls[$header_index][2] = $value[2];
				$header_index = $index;
			}
			if ($value[0] === 'section_open') {
				$calls[$s_open_index][2] = $value[2];
				$s_open_index = $index;
			}
			if ($value[0] === 'section_close') {
				$calls[$s_close_index][2] = $value[2];
				$s_close_index = $index;
			}
		}
		dbglog($calls, 'calls');
		// scan instructions for edit sections
		$size = count($calls);
		for ($i=0; $i<$size; $i++) {
			if ($calls[$i][0]=='section_edit') {
				$edits[] =& $calls[$i];
			}
		}
		
		// rewrite edit section instructions
		$last = max(count($edits)-1,0);
		for ($i=0; $i<=$last; $i++) {
			$end = 0;
			// get data to move
			$start = $edits[min($i+1,$last)][1][0];
			$level = $edits[min($i+1,$last)][1][2];
			$name  = $edits[min($i+1,$last)][1][3];
			// find the section end point
			if ($order) {
				$finger = $i+2;
				while (isset($edits[$finger]) && $edits[$finger][1][2]>$level) {
					$finger++;
				}
				if (isset($edits[$finger])) {
					$end = $edits[$finger][1][0]-1;
				}
			} else {
				$end = $edits[min($i+1,$last)][1][1];
			}
			// put the data back where it belongs
			$edits[$i][1][0] = $start;
			$edits[$i][1][1] = $end;
			$edits[$i][1][2] = $level;
			$edits[$i][1][3] = $name;
		}
		$edits[max($last-1,0)][1][1] = 0;  // set new last section
		$edits[$last][1][0] = -1; // hide old last section
	}
}

// vim:ts=4:sw=4:et:enc=utf-8:
