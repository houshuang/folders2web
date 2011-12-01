<?php

/**
 * Instruction re-writer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

if (!class_exists('instruction_rewriter', false)) {

class instruction_rewriter {

    var $correction;

    /**
     * Constructor
     */
    function instruction_rewriter() {
        $this->correction = array();
    }

    /**
     *
     */
    function addCorrections($correction) {
        foreach ($correction as $c) {
            $this->correction[$c->getIndex()][] = $c;
        }
    }

    /**
     *
     */
    function process(&$instruction) {
        if (count($this->correction) > 0) {
            $index = $this->_getCorrectionIndex();
            $corrections = count($index);
            $instructions = count($instruction);
            $output = array();
            for ($c = 0, $i = 0; $c < $corrections; $c++, $i++) {
                /* Copy all instructions that are before the next correction */
                for ( ; $i < $index[$c]; $i++) {
                    $output[] = $instruction[$i];
                }
                /* Apply the corrections */
                $preventDefault = false;
                foreach ($this->correction[$i] as $correction) {
                    $preventDefault = ($preventDefault || $correction->apply($instruction, $output));
                }
                if (!$preventDefault) {
                    $output[] = $instruction[$i];
                }
            }
            /* Copy the rest of instructions after the last correction */
            for ( ; $i < $instructions; $i++) {
                $output[] = $instruction[$i];
            }
            /* Handle appends */
            if (array_key_exists(-1, $this->correction)) {
                $this->correction[-1]->apply($instruction, $output);
            }
            $instruction = $output;
        }
    }

    /**
     *
     */
    function _getCorrectionIndex() {
        $result = array_keys($this->correction);
        asort($result);
        /* Remove appends */
        if (reset($result) == -1) {
            unset($result[key($result)]);
        }
        return array_values($result);
    }
}

class instruction_rewriter_correction {

    var $index;

    /**
     * Constructor
     */
    function instruction_rewriter_correction($index) {
        $this->index = $index;
    }

    /**
     *
     */
    function getIndex() {
        return $this->index;
    }
}

class instruction_rewriter_delete extends instruction_rewriter_correction {

    /**
     * Constructor
     */
    function instruction_rewriter_delete($index) {
        parent::instruction_rewriter_correction($index);
    }

    /**
     *
     */
    function apply($input, &$output) {
        return true;
    }
}

class instruction_rewriter_call_list extends instruction_rewriter_correction {

    var $call;

    /**
     * Constructor
     */
    function instruction_rewriter_call_list($index) {
        parent::instruction_rewriter_correction($index);
        $this->call = array();
    }

    /**
     *
     */
    function addCall($name, $data) {
        $this->call[] = array($name, $data);
    }

    /**
     *
     */
    function addPluginCall($name, $data, $state, $text = '') {
        $this->call[] = array('plugin', array($name, $data, $state, $text));
    }

    /**
     *
     */
    function appendCalls(&$output, $position) {
        foreach ($this->call as $call) {
            $output[] = array($call[0], $call[1], $position);
        }
    }
}

class instruction_rewriter_insert extends instruction_rewriter_call_list {

    /**
     * Constructor
     */
    function instruction_rewriter_insert($index) {
        parent::instruction_rewriter_call_list($index);
    }

    /**
     *
     */
    function apply($input, &$output) {
        $this->appendCalls($output, $input[$this->index][2]);
        return false;
    }
}

class instruction_rewriter_append extends instruction_rewriter_call_list {

    /**
     * Constructor
     */
    function instruction_rewriter_append() {
        parent::instruction_rewriter_call_list(-1);
    }

    /**
     *
     */
    function apply($input, &$output) {
        $lastCall = end($output);
        $this->appendCalls($output, $lastCall[2]);
        return false;
    }
}

}
