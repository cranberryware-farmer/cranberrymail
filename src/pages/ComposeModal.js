import React, { useEffect } from 'react';
import {
    Button as RButton,
    Form,
    FormGroup,
    Input,
    Label,
    Row,
    Col,
    FormText,
    Modal,
    ModalBody,
    Spinner,
} from 'reactstrap';
import { MdAttachFile } from "react-icons/md";
// import { Editor } from 'react-draft-wysiwyg';
import CustomToolbarEditor from 'components/customEditor';
import Dock from 'react-dock';
import {
  RiSendPlaneLine,
} from 'react-icons/ri';
import {
  FaWindowMaximize,
  FaRegWindowMinimize,
} from 'react-icons/fa';
import {isMobile} from 'react-device-detect';
import { toast } from 'react-toastify';


const dockStyles = {
  position: 'fixed',
  zIndex: 1,
  boxShadow: '0 0 4px rgba(0, 0, 0, 0.3)',
  background: 'white',
  left: isMobile ? '10px' : 'unset',
  top: 'unset',
  width: isMobile ? '90%' : '40%',
  height: isMobile ? '100%' : '70%',
  right: '10px',
  bottom: '5px',
  'border-radius': '15px 15px 0px 0px',
  overflow: 'hidden',
};

const minDockStyles = {
  position: 'fixed',
  zIndex: 1,
  boxShadow: '0 0 4px rgba(0, 0, 0, 0.3)',
  background: 'white',
  left: 'unset',
  top: 'unset',
  width: '25%',
  height: '10%',
  right: '10px',
  bottom: '5px',
  'border-radius': '15px 15px 0px 0px',
  overflow: 'hidden',
};

const composeModal = (props) => {
  const dockState = props.dockState;

  let headerLeft, headerRight;
  let hiddenClass = '',
      hiddenAction = false;

  if(dockState === "maximize"){
    headerLeft = 8;
    headerRight = 4;
  } else if(dockState === "minimize"){
    headerLeft = 7;
    headerRight = 5;
    hiddenClass = 'd-none';
    if(isMobile){
      hiddenAction = true;
    }
  } else {
    headerLeft = 9;
    headerRight = 3;
  }

  useEffect(() => {
    // Update the document title using the browser API
    if(props.attachmentContent) {
      document.getElementById('attachment').files = props.attachmentContent;
    }
  });

  const sendEmail = () => {
    if(!props.isSending) {
      const toValue = document.getElementById('to').value;
      if(toValue) {
        props.sendemail();
      } else {
        toast("Empty to Field");
      }
    }
  }

  const composeContent = (
    <div>
      <Row className='bg-secondary text-white ml-0 mr-0 cm-h-30p cm-sticky-header'>
        <Col md={headerLeft} sm={headerLeft} xs={headerLeft} className='mt-1 mb-1'>
          <div className='ml-2'> New Email </div>
        </Col>
        <Col md={headerRight} sm={headerRight} xs={headerRight} className='mt-1 mb-0'>
          <Row className='ml-0 mr-0'>
            <Col md='4' sm='4' xs='4'>
              <FaRegWindowMinimize 
                className='cm-pointer'
                onClick = {
                  () => {
                    if(dockState === "minimize") {
                      props.resizeDock("normal");
                    } else {
                      props.resizeDock("minimize");
                    }
                  }
                }
                hidden={hiddenAction}
              />
            </Col>
            <Col md='4' sm='4' xs='4'>
              <FaWindowMaximize 
                className='cm-pointer'
                onClick = {
                  () => {
                    if(dockState === "maximize"){
                      props.resizeDock("normal");
                    } else {
                      props.resizeDock("maximize");
                    }
                  }
                }
                hidden={hiddenAction}
              />
            </Col>
            <Col md='4' sm='4' xs='4'>
              <span
                className='font-weight-bold cm-pointer'
                onClick = {
                  () => {
                    props.closemodal();
                  }
                }
              > X </span>
            </Col>
          </Row>
        </Col>
      </Row>

      <div className={hiddenClass}>
        <Form onSubmit={() => { props.sendemail() }} className="m-1">
          <FormGroup>
            <Input
              type="text"
              name="to"
              id="to"
              placeholder="To:"
              className='cm-h-30p'
              defaultValue={props.to}
              required
            />
            <Input
              type="text"
              name="text"
              id="cc"
              placeholder="CC:"
              className='cm-h-30p mt-1'
              defaultValue={props.cc}
            />
            <Input
              type="text"
              name="bcc"
              id="bcc"
              placeholder="Bcc:"
              className='cm-h-30p mt-1'
              defaultValue={props.bcc}
            />
            <FormText color="muted"> Email addresses to be separated by ,</FormText>
            <Input
              type="text"
              name="subject"
              id="subject"
              placeholder="Subject:"
              className='cm-h-30p mt-1'
              defaultValue={props.subject}
            />
          </FormGroup>
          <FormGroup>
            {/* <Editor
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
              /> */}
            <CustomToolbarEditor 
              id="email"
              editorState={props.meditorstate}
              content={ props.content }
              onEditorStateChange={(es) => {
                props.meditorstatehandler(es);
              }}
            />
          </FormGroup>
          <FormGroup>
            <RButton 
              onClick={() => sendEmail()}
              disabled={props.isSending}
            >
              Send  
              { props.isSending ? <Spinner 
                type = "grow"
                color = "light"
                size="sm"
                className='send-spinner'
              /> : <RiSendPlaneLine /> }
            </RButton>
            <Label for="attachment" className='float-right'><MdAttachFile /></Label>
            <Input
              type="file"
              name="attachment"
              id="attachment"
              multiple="multiple"
              onChange={ (ev) => {
                props.attachmenthandler(ev);
              }}
              hidden
            />
          </FormGroup>
        </Form>
      </div>
    </div>
  );

  if(dockState === "maximize"){
    return (
      <Modal
        isOpen={props.modal}
        toggle={() => { props.closemodal() }}
        className={props.classnm}
      >
        <ModalBody>
          {composeContent}
        </ModalBody>
      </Modal>
    ); 
  } else if(dockState === "minimize"){
    return (
      <Dock 
        position='bottom'
        isVisible={ props.modal }
        dimMode='none'
        fluid={true}
        size='0.1'
        dockStyle={ minDockStyles }
      >
        {composeContent}
      </Dock>
    ); 
  } else {
    return (
      <Dock 
        position='bottom'
        isVisible={ props.modal }
        dimMode='none'
        fluid={true}
        size='0.82'
        dockStyle={ dockStyles }
      >
        {composeContent}
      </Dock>
    );
  }
}

export default composeModal;