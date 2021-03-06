<?php

abstract class ActiveRecord extends CActiveRecord {

    protected function afterFind() {
        foreach ($this->metadata->tableSchema->columns as $columnName => $column) {
            if ($this->$columnName === null)
                continue;
            switch ($column->dbType) {
                case 'timestamp':
                    if($this->$columnName != "0000-00-00 00:00:00")
                        $this->$columnName = date("Y-m-d H:i:s", strtotime($this->$columnName." UTC"));
                    break;

                case 'datetime':
                    if($this->$columnName != "0000-00-00 00:00:00")
                        $this->$columnName = date("Y-m-d H:i:s", strtotime($this->$columnName." UTC"));
                    break;
            }
        }
        return parent::afterFind();
    }

    protected function beforeSave() {
        foreach ($this->metadata->tableSchema->columns as $columnName => $column) {
            if ($this->$columnName === null)
                continue;
            if ($this->$columnName instanceof CDbExpression)
                continue;
            switch ($column->dbType) {
                case 'timestamp':
                    $this->$columnName = gmdate("Y-m-d H:i:s", strtotime($this->$columnName));
                    break;

                case 'datetime':
                    $this->$columnName = gmdate("Y-m-d H:i:s", strtotime($this->$columnName));
                    break;
            }
        }
        return parent::beforeSave();
    }

    protected function afterSave() {
        foreach ($this->metadata->tableSchema->columns as $columnName => $column) {
            if ($this->$columnName === null)
                continue;
            if ($this->$columnName instanceof CDbExpression)
                continue;
            switch ($column->dbType) {
                case 'timestamp':
                    $this->$columnName = date("Y-m-d H:i:s", strtotime($this->$columnName." UTC"));
                    break;

                case 'datetime':
                    $this->$columnName = date("Y-m-d H:i:s", strtotime($this->$columnName." UTC"));
                    break;
            }
        }
        return parent::afterSave();
    }
}

?>
