import React from 'react';
import {
    Button as RButton,
    Card,
    CardBody,
    CardHeader,
    Col,
    Modal,
    ModalBody,
    ModalHeader,
    Form,
    FormGroup,
    FormText,
    Input,
    Label,
    Row,
} from 'reactstrap';
import { MdAttachFile } from "react-icons/md";
import { Editor } from 'react-draft-wysiwyg';

const composeModal = (props) => {
 return (<Row>
    <Col md="12" sm="12" xs="12">
      <Card>
        <CardHeader>Modal</CardHeader>
        <CardBody>
          <Modal
            isOpen={props.modal}
            toggle={() => { props.closemodal() }}
            className={props.classnm}
          >
            <ModalHeader toggle={() => { props.closemodal() }}>
              Compose Email
            </ModalHeader>
            <ModalBody>
              <Form onSubmit={() => { props.sendemail() }}>
                <FormGroup>
                  <Label for="to">To</Label>
                  <Input
                    type="text"
                    name="to"
                    id="to"
                    placeholder="Send email to..."
                    required
                  />
                  <FormText color="muted"> Email addresses to be separated by ,</FormText>
                </FormGroup>
                <FormGroup>
                  <Label for="cc">Cc</Label>
                  <Input
                    type="text"
                    name="text"
                    id="cc"
                    placeholder="Send copy of email to..."
                  />
                  <FormText color="muted"> Email addresses to be separated by ,</FormText>
                </FormGroup>
                <FormGroup>
                  <Label for="bcc">Bcc</Label>
                  <Input
                    type="text"
                    name="bcc"
                    id="bcc"
                    placeholder="Send copy of email to..."
                  />
                  <FormText color="muted"> Email addresses to be separated by ,</FormText>
                </FormGroup>
                <FormGroup>
                  <Label for="subject">Subject</Label>
                  <Input
                    type="text"
                    name="subject"
                    id="subject"
                    placeholder="Subject of the email"
                  />
                </FormGroup>
                <FormGroup>
                  <Label for="email">Email</Label>
                  <Editor
                    editorState={props.meditorstate}
                    wrapperClassName="demo-wrapper"
                    editorClassName="demo-editor"
                    onEditorStateChange={(es) => { 
                      
                      props.meditorstatehandler(es);
                     }}
                    id="email"
                    toolbarClassName="toolbar-class"
                    toolbar={{
                      inline: { inDropdown: true },
                      list: { inDropdown: true },
                      textAlign: { inDropdown: true },
                      link: { inDropdown: true },
                      history: { inDropdown: true },
                    }}
                    />
                </FormGroup>
                <FormGroup>
                  <Label for="attachment"><MdAttachFile /> Attachment</Label>
                  <Input
                    type="file"
                    name="attachment"
                    id="attachment"
                    multiple="multiple"
                    onChange={ (ev) => {
                     props.attachmenthandler(ev);
                    }}
                  />
                  <FormText color="muted">
                    Maximum allowed file size 20MB
                  </FormText>
                </FormGroup>
                <RButton onClick={ () =>  props.sendemail() }>
                  Send Email
                </RButton>
              </Form>
            </ModalBody>
          </Modal>
        </CardBody>
      </Card>
    </Col>
    </Row>);
}

export default composeModal;