<?php

class ViewContactIndex extends CoreView
{

    protected $helpers = ['Sessions', 'Forms', 'Table', 'Pagination'];

    //table column names
    protected $columnNames = [
        'firstName' => 'First Name',
        'lastName'  => 'Last Name',
        'email'     => 'Email',
        'phone'     => 'Best phone'
    ];

    protected $additionalСolumns = [
        //name   => executable file
        'edit'   => 'update',
        'delete' => 'delete'
    ];

    public function render($data)
    {
        $headres = $this->Table->tableHeaders($this->columnNames, $this->additionalСolumns, $data['sorting'], $data['pagination']['page']);
        $dataForTable = $this->renderData($data['contacts']);

        $table = "
            <div class = 'tableBlock' id = 'tableBlock'>
                <table cellpadding = '10' id = 'table'>
                    <tr>
                        $headres
                    </tr>
                    $dataForTable
                </table>
            </div>
            <br/>";

        echo $table;
        echo $this->Pagination->getPagination($data);
    }

    public function renderData($data)
    {
        $renderedData = '';
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $renderedData .= "
                    <tr id = " . $value['id'] . ">
                        <td>" . $value['firstName'] . " </td>
                        <td>" . $value['lastName'] . " </td>
                        <td>" . $value['email'] . " </td>
                        <td>" . $value['phone'] . " </td>
                        <td><a href = '/contact/edit/" . $value['id'] . "' class='button'>edit</a></td>
                        <td><a href = '/contact/delete/" . $value['id'] . "' class='button'>delete</a></td>
                    </tr> ";
            }
        }
        return $renderedData;
    }
}
