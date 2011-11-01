<?php

App::uses('AppController', 'Controller');

/**
 * Pets Controller
 *
 * @property Pet $Pet
 */
class PetsController extends AppController {

    var $components = array('Attachment' => array(
            'allow_non_image_files' => false
            ));

    /**
     * index method
     *
     * @return void
     */
    public function index() {
        $this->Pet->recursive = 0;
        $this->set('pets', $this->paginate());
    }

    /**
     * view method
     *
     * @param string $id
     * @return void
     */
    public function view($id = null) {
        $this->Pet->id = $id;
        if (!$this->Pet->exists()) {
            throw new NotFoundException(__('Invalid pet'));
        }
        $this->set('pet', $this->Pet->read(null, $id));
    }

    /**
     * add method
     *
     * @return void
     */
    public function add() {
        if ($this->request->is('post')) {
            $this->Pet->create();

            $image_ok = true;
            if ($this->Attachment->upload($this->request->data['Pet'])) {
                
            } else {
                $image_ok = false;
            }

            if (($image_ok) && ($this->Pet->save($this->request->data))) {
                $this->Session->setFlash(__('The pet has been saved'));
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('The pet could not be saved. Please, try again.'));
            }
        }
    }

    /**
     * edit method
     *
     * @param string $id
     * @return void
     */
    public function edit($id = null) {
        $this->Pet->id = $id;
        if (!$this->Pet->exists()) {
            throw new NotFoundException(__('Invalid pet'));
        }
        if ($this->request->is('post') || $this->request->is('put')) {
            if ($this->Pet->save($this->request->data)) {
                $this->Session->setFlash(__('The pet has been saved'));
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('The pet could not be saved. Please, try again.'));
            }
        } else {
            $this->request->data = $this->Pet->read(null, $id);
        }
    }

    /**
     * delete method
     *
     * @param string $id
     * @return void
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        $this->Pet->id = $id;
        if (!$this->Pet->exists()) {
            throw new NotFoundException(__('Invalid pet'));
        }
        if ($this->Pet->delete()) {
            $this->Session->setFlash(__('Pet deleted'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Session->setFlash(__('Pet was not deleted'));
        $this->redirect(array('action' => 'index'));
    }

}
