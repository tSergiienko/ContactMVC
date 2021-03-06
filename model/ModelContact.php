<?php

class ModelContact extends CoreModel
{
    protected $components = ['Validate', 'Sorting', 'Pagination'];

    protected $labelsOfContact = [
        'user_name',
        'user_surname',
        'user_mail',
        'bestPhone',
        'user_hPhone',
        'user_wPhone',
        'user_cPhone',
        'user_address1',
        'user_address2',
        'user_city',
        'user_state',
        'user_zip',
        'user_country',
        'user_birthday'
    ];

    protected $sortParams = '';
    protected $paginationParams = '';

    public function getLabelsOfContact()
    {
        return $this->labelsOfContact;
    }

    public function getParamsSorting()
    {
        return $this->sortParams;
    }

    public function getParamsPagination()
    {
        return $this->paginationParams;
    }

    private function getUserID()
    {    
        try {
            $session = new ModelSessions;
            return $session->getUserID();
        } catch (Exception $e) {
            echo 'Exception: ',  $e->getMessage(), "\n"; //TODO
        }
    }

    public function selectDataForMainPage($param, $numberOfRecords)
    {
        $sortParams = $this->getSortParams($param);
        $column = ($sortParams['column'] == 'phone') ? "contact_phones.". $sortParams['column'] : "contact_list.". $sortParams['column'];
        $limit = $this->Pagination->getLimitParams($param, $numberOfRecords);
        $this->paginationParams = $limit;
        $userId = $this->getUserID();

        $selectQuery = "SELECT contact_list.id, contact_list.firstName, contact_list.lastName, contact_list.email, contact_phones.phone
                            FROM contact_list 
                                INNER JOIN contact_phones 
                                    ON contact_list.id = contact_phones.contactId
                                        WHERE contact_list.userId      = $userId
                                        AND contact_list.favoritePhone = contact_phones.phoneType
                                            ORDER BY " . $column . ' ' . $sortParams['sort'] . "
                                                LIMIT " . $limit['pageFirstResult'] . ',' . $limit['resultsPerPage'] . "";

        $resultSelect = CoreDB::getInstance()->selectFromDB($selectQuery);
        $this->sortParams['sort'] = $this->Sorting->changeSortBy($param);
        return $resultSelect;
    }

    private function getSortParams($param)
    {
        $ViewContactIndex = new ViewContactIndex($param); //TODO Can i do this? I give param but it is not needed

        $sortParams['column'] = $this->Sorting->getColumn($param, array_keys($ViewContactIndex->getColumnNames()));
        $sortParams['sort'] = $this->Sorting->getSortBy($param); //TODO
        //$this->Sorting->changeSortBy($param);
        $this->sortParams = $sortParams;
        return $sortParams;
    }

    public function deleteContacts($idLine)
    {
        $forEscape['idLine'] = $idLine;
        $escapedData = CoreDB::getInstance()->escapeData($forEscape);
        $userId = $this->getUserID();
        return CoreDB::getInstance()->delete("DELETE FROM contact_list WHERE id = '" . $escapedData['idLine'] . "'AND userId = '" . $userId . "'");
    }

    public function isDeleted($statement)
    {
        $session = new ModelSessions;
        if ($statement === true) {
            $msg['deleted'] = true;
            $session->recordMessageInSession('delete', $msg);
        } else {
            $msg['notDelete'] = true;
            $session->recordMessageInSession('delete', $msg);
        }
        header("Location: /contact/index");
    }

    public function insertDataToContactList($data)
    {
        $data = CoreDB::getInstance()->escapeData($data);
        $insertQuery = "INSERT INTO contact_list (userId, firstName, lastName, email, favoritePhone) VALUES (
            '" . $data['userId'] . "',
            '" . $data['user_name'] . "',
            '" . $data['user_surname'] . "',
            '" . $data['user_mail'] . "',
            '" . $data['bestPhone'] . "'
        )";
        $resultInsert = CoreDB::getInstance()->insertToDB($insertQuery);
        $lastContactID = CoreDB::getInstance()->getLastID($resultInsert);
        return $lastContactID;
    }
    
    public function insertDataToContactPhones($contactID, $data)
    {
        $data = CoreDB::getInstance()->escapeData($data);
        foreach ($data as $key => $value) {
            $insertQuery = "INSERT INTO contact_phones (contactId, phone, phoneType) VALUES (
                '" . $contactID . "',
                '" . $value . "',
                '" . $key . "'
            )";
            $resultInsert = CoreDB::getInstance()->insertToDB($insertQuery);
        }
        return $resultInsert;
    }

    public function insertDataToContactAddress($contactID, $data)
    {
        $data = CoreDB::getInstance()->escapeData($data);
        $insertQuery = "INSERT INTO contact_address (contactId, address1, address2, city, state, zip, country, birthday) VALUES (
            '" . $contactID . "',
            '" . $data['user_address1'] . "',
            '" . $data['user_address2'] . "',
            '" . $data['user_city'] . "',
            '" . $data['user_state'] . "',
            '" . $data['user_zip'] . "',
            '" . $data['user_country'] . "',
            '" . $data['user_birthday'] . "'
        )";
        $resultInsert = CoreDB::getInstance()->insertToDB($insertQuery);
        return $resultInsert;
    }

    public function isInserted($statement)
    {
        $session = new ModelSessions;
        if ($statement === true) {
            $msg['add'] = true;
            $session->recordMessageInSession('insert', $msg);
            header("Location: /contact/index");
        } else {
            $msg['notAdd'] = true;
            $session->recordMessageInSession('insert', $msg);
        }
    }

    public function selectAllData($idLine)
    {
        $forEscape['idLine'] = $idLine;
        $escapedData = CoreDB::getInstance()->escapeData($forEscape);

        $userId = $this->getUserID();
        $selectQuery = "SELECT contact_list.id, contact_list.firstName, contact_list.lastName, contact_list.email, contact_list.favoritePhone, contact_address.address1, contact_address.address2, contact_address.city, contact_address.state, contact_address.zip, contact_address.country, contact_address.birthday 
                FROM contact_list 
                    INNER JOIN contact_address 
                        ON contact_list.id = contact_address.contactId
                            WHERE contact_list.userId = '" . $userId . "' AND contact_list.id = '" . $escapedData['idLine'] . "'";

        $resultSelect = CoreDB::getInstance()->selectFromDB($selectQuery);
        return $resultSelect;
    }

    public function selectPhones($idLine)
    {
        $forEscape['idLine'] = $idLine;
        $escapedData = CoreDB::getInstance()->escapeData($forEscape);
        
        $userId = $this->getUserID();
        $selectQuery = "SELECT contact_phones.phone, contact_phones.phoneType 
                            FROM contact_phones 
                                WHERE contact_phones.contactId = '" . $escapedData['idLine'] . "'";

        $resultSelect = CoreDB::getInstance()->selectFromDB($selectQuery);
        return $resultSelect;
    }

    public function updateDataInContactList($idContact, $data)
    {
        $forEscape['idLine'] = $idContact;
        $idContact = CoreDB::getInstance()->escapeData($forEscape);
        $data = CoreDB::getInstance()->escapeData($data);

        $userId = $this->getUserID();
        $updateQuery = "UPDATE contact_list 
            SET firstName     = '" . $data['user_name'] . "',
                lastName      = '" . $data['user_surname'] . "',
                email         = '" . $data['user_mail'] . "',
                favoritePhone = '" . $data['bestPhone'] . "' 
                    WHERE contact_list.id   = '" . $idContact['idLine'] . "' 
                        AND contact_list.userId = '" . $userId . "'";

        $resultUpdate = CoreDB::getInstance()->updateDB($updateQuery);
        return $resultUpdate;
    }

    public function updateDataToContactPhones($idContact, $data)
    {
        $forEscape['idLine'] = $idContact;
        $idContact = CoreDB::getInstance()->escapeData($forEscape);
        $data = CoreDB::getInstance()->escapeData($data);

        foreach ($data as $key => $value) {
            $updateQuery = "INSERT INTO contact_phones (contactId, phone, phoneType) VALUES (
                '" . $idContact['idLine'] . "',
                '" . $value . "',
                '" . $key . "'
                ) ON DUPLICATE KEY UPDATE phone = '" . $value . "'";

            $resultUpdate = CoreDB::getInstance()->updateDB($updateQuery);
        }
        return $resultUpdate;
    }

    public function updateDataToContactAddress($idContact, $data)
    {
        $forEscape['idLine'] = $idContact;
        $idContact = CoreDB::getInstance()->escapeData($forEscape);
        $data = CoreDB::getInstance()->escapeData($data);

        $userId = $this->getUserID();
        $updateQuery = "UPDATE contact_address, contact_list 
            SET address1 = '" . $data['user_address1'] . "',
                address2 = '" . $data['user_address2'] . "',
                city     = '" . $data['user_city'] . "',
                state    = '" . $data['user_state'] . "',
                zip      = '" . $data['user_zip'] . "',
                country  = '" . $data['user_country'] . "',
                birthday = '" . $data['user_birthday'] . "' 
                    WHERE contact_address.contactId = '" . $idContact['idLine'] . "' 
                        AND contact_list.userId     = '" . $userId . "'";

        $resultUpdate = CoreDB::getInstance()->updateDB($updateQuery);
        return $resultUpdate;
    }

    public function isUpdated($statement)
    {
        $session = new ModelSessions;
        if ($statement === true) {
            $msg['updated'] = true;
            $session->recordMessageInSession('update', $msg);
            header("Location: /contact/index");
        } else {
            $msg['notUpdated'] = true;
            $session->recordMessageInSession('update', $msg);
        }
    }

    public function getDataForEdit($selectedData, $phones)
    {
        foreach ($selectedData as $key => $value) {
            $dataForEdit = [
                'selectedRadio' => $value['favoritePhone'],
                'data' => [
                    'user_name'     => $value['firstName'],
                    'user_surname'  => $value['lastName'],
                    'user_mail'     => $value['email'],
                    'user_hPhone'   => $phones['hPhone'],
                    'user_wPhone'   => $phones['wPhone'],
                    'user_cPhone'   => $phones['cPhone'],
                    'user_address1' => $value['address1'],
                    'user_address2' => $value['address2'],
                    'user_city'     => $value['city'],
                    'user_state'    => $value['state'],
                    'user_zip'      => $value['zip'],
                    'user_country'  => $value['country'],
                    'user_birthday' => $value['birthday'],
                ]
            ];
        }
        return $dataForEdit;
    }

    public function getCountFromContactList()
    {
        $userId = $this->getUserID();
        $selectQuery = "SELECT COUNT(contact_list.id) AS amt 
                        FROM contact_list, contact_phones 
                            WHERE contact_list.id          = contact_phones.contactId 
                            AND contact_list.userId        = $userId
                            AND contact_list.favoritePhone = contact_phones.phoneType";

        $resultSelect = CoreDB::getInstance()->selectFromDB($selectQuery);
        return $resultSelect;
    }
}
