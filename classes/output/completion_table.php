<?php

namespace tool_import_completion\output;

class completion_table {

    private $_row;
    public $columns = array('line', 'id', 'username', 'firstname', 'lastname', 'course',
        'completiondate', 'status', 'grade', 'moduleid', 'dategraded');


    /**
     * Print table header.
     * @return void
     */
    public function start($importing = 0) {
        $ci = 0;
        echo '<form action="#" method="post">';
        echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="' .
            get_string('uploadusersresult', 'tool_uploaduser').'">';
        echo '<tr class="heading r0">';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('uucsvline', 'tool_import_completion').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">ID</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('username').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('firstname').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('lastname').'</th>';
        if ($importing == 0) {
            echo '<th class="header c'.$ci++.'" scope="col">'.get_string('course').'</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' .
                get_string('completiondate', 'tool_import_completion') . '</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('status', 'tool_import_completion') . '</th>';
        } else {
            echo '<th class="header c'.$ci++.'" scope="col">'.get_string('coursemodule', 'tool_import_completion').'</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('grade', 'tool_import_completion') . '</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('dategraded', 'tool_import_completion') . '</th>';
            echo '<th class="header c' . $ci++ . '" scope="col">' .
                get_string('completiondate', 'tool_import_completion') . '</th>';
        }

        echo '</tr>';
        $this->_row = null;
    }

    /**
     * Flush previous line and start a new one.
     * @return void
     */
    public function flush() {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number.
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r'.$ri.'">';
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu'.$type.'">'.$field[$type].'</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c'.$ci++.'">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    /**
     * Add tracking info
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true) {
        if (empty($this->_row)) {
            $this->flush(); // Init arrays.
        }

        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:'.$col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .= '<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Print the table end
     * @return void
     */
    public function close($iid, $filecolumns, $readcount, $mapping, $dateformat, $importing) {
        $this->flush();
        $filecolumns = implode (',', $filecolumns);
        $text = "Upload Completions";
        if ($importing == 1) {
            $text = "Upload Grades";
        }
        echo '</table>';
        echo "<input type ='hidden' name='iid' value={$iid}>";
        echo "<input type ='hidden' name='filecolumns' value={$filecolumns}>";
        echo "<input type ='hidden' name='readcount' value={$readcount}>";
        echo "<input type ='hidden' name='mapping' value={$mapping}>";
        echo "<input type ='hidden' name='dateformat' value={$dateformat}>";
        echo "<input type ='hidden' name='dataimport' value={$importing}>";
        echo "<input type ='submit' class='btn btn-primary' name='uploadcompletion' value='{$text}'>";
        echo '</form>';
    }

}