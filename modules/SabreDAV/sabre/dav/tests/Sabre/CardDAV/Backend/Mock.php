<?php

namespace Sabre\CardDAV\Backend;

class Mock extends AbstractBackend {

    public $addressBooks;
    public $cards;

    function __construct($addressBooks = null, $cards = null) {

        $this->addressBooks = $addressBooks;
        $this->cards = $cards;

        if (is_null($this->addressBooks)) {
            $this->addressBooks = array(
                array(
                    'id' => 'foo',
                    'uri' => 'book1',
                    'principaluri' => 'principals/user1',
                    '{DAV:}displayname' => 'd-name',
                ),
            );

            $card2 = fopen('php://memory','r+');
            fwrite($card2,"BEGIN:VCARD\nVERSION:3.0\nUID:45678\nEND:VCARD");
            rewind($card2);
            $this->cards = array(
                'foo' => array(
                    'card1' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
                    'card2' => $card2,
                ),
            );
        }

    }


    function getAddressBooksForUser($principalUri) {

        $books = array();
        foreach($this->addressBooks as $book) {
            if ($book['principaluri'] === $principalUri) {
                $books[] = $book;
            }
        }
        return $books;

    }

    /**
     * Updates properties for an address book.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documenation for more info and examples.
     *
     * @param string $addressBookId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {

        foreach($this->addressBooks as &$book) {
            if ($book['id'] !== $addressBookId)
                continue;

            $propPatch->handleRemaining(function($mutations) use (&$book) {
                foreach($mutations as $key=>$value) {
                    $book[$key] = $value;
                }
                return true;
            });

        }

    }

    function createAddressBook($principalUri, $url, array $properties) {

        $this->addressBooks[] = array_merge($properties, array(
            'id' => $url,
            'uri' => $url,
            'principaluri' => $principalUri,
        ));

    }

    function deleteAddressBook($addressBookId) {

        foreach($this->addressBooks as $key=>$value) {
            if ($value['id'] === $addressBookId)
                unset($this->addressBooks[$key]);
        }
        unset($this->cards[$addressBookId]);

    }

    function getCards($addressBookId) {

        $cards = array();
        foreach($this->cards[$addressBookId] as $uri=>$data) {
            $cards[] = array(
                'uri' => $uri,
                'carddata' => $data,
            );
        }
        return $cards;

    }

    function getCard($addressBookId, $cardUri) {

        if (!isset($this->cards[$addressBookId][$cardUri])) {
            return false;
        }

        return array(
            'uri' => $cardUri,
            'carddata' => $this->cards[$addressBookId][$cardUri],
        );

    }

    function createCard($addressBookId, $cardUri, $cardData) {

        $this->cards[$addressBookId][$cardUri] = $cardData;

    }

    function updateCard($addressBookId, $cardUri, $cardData) {

        $this->cards[$addressBookId][$cardUri] = $cardData;

    }

    function deleteCard($addressBookId, $cardUri) {

        unset($this->cards[$addressBookId][$cardUri]);

    }

}
