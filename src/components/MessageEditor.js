import React, { Component } from 'react';
import { draftToMarkdown, markdownToDraft } from 'markdown-draft-js';
import { EditorState, convertFromRaw, convertToRaw } from 'draft-js';
import { Editor } from 'react-draft-wysiwyg';
import draftToHtml from 'draftjs-to-html';
import {stateFromHTML} from 'draft-js-import-html';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';
import { Button as RButton, Form, FormGroup, Label, Input, Col,FormText } from 'reactstrap';
import { FaTrash } from 'react-icons/fa';
import { MdAttachFile } from "react-icons/md";

class MessageEditor extends Component {
  state = {
    editorState: EditorState.createEmpty(),
    isCc: false,
    isBcc: false,
  };
  constructor(props){
    super(props);
    let contentState = {};
    if(this.props.markdown){
      const markdownString = this.props.fwdMsg;
      const rawData = markdownToDraft(markdownString);
      contentState = convertFromRaw(rawData);
    }else{
      contentState = stateFromHTML(this.props.fwdMsg);
    }
    let editorState = EditorState.createWithContent(contentState);
    this.state = {editorState: editorState};
  }

  componentDidMount(){
    if(this.props.isEmailFwd===true){
      document.getElementById("staticEmail").value="";
      if(this.state.isCc){
        document.getElementById("cc").value="";
      }
      if(this.state.isBcc){
        document.getElementById("bcc").value="";
      }
    }
    if(this.props.orCc.length > 0 && this.state.isCc===false){
      this.setState({
        isCc: true
      });
    }

    if(this.props.orBcc.length > 0 && this.state.isBcc===false){
      this.setState({
        isBcc: true
      });
    }
  }

  onEditorStateChange = editorState => {
    this.setState({
      editorState,
    });
  };

  render() {
    const { editorState } = this.state;
    return (
      <div>
        <div>
          <Form>
            <FormGroup row>
              <Label for="staticEmail" sm={2}>To</Label>
              <Col sm={10} >
                <Input
                  type="text"
                  className="form-control-plaintext border-bottom"
                  id="staticEmail"
                  defaultValue={this.props.orFrom}
                  placeholder = "To"
                />
                <FormText color="muted"> Email addresses to be separated by ,</FormText>
              </Col>
            </FormGroup>
          {this.state.isCc ? (
            <FormGroup row>
              <Label for="cc" sm={2}>Cc</Label>
              <Col sm={10} >
              <Input
                type="text"
                className="form-control-plaintext border-bottom"
                id="cc"
                placeholder="Cc"
                defaultValue={this.props.isEmailFwd ? '': this.props.orCc}
              />
              <FormText color="muted"> Email addresses to be separated by ,</FormText>
              </Col>
            </FormGroup>

          ) : (
            <a
              href="#cc"
              onClick={() => {
                this.setState({ isCc: true });
              }}
            >
              CC
            </a>
          )}
          <span>&nbsp;</span>
          {this.state.isBcc ? (
            <FormGroup row>
              <Label for="bcc" sm={2}>Bcc</Label>
              <Col sm={10}>
              <Input
                label="Bcc"
                type="text"
                className="form-control-plaintext border-bottom"
                id="bcc"
                placeholder="Bcc"
                defaultValue={this.props.isEmailFwd ? '': this.props.orBcc}
              />
              <FormText color="muted"> Email addresses to be separated by ,</FormText>
              </Col>
            </FormGroup>

          ) : (
            <a
              href="#bcc"
              onClick={() => {
                this.setState({ isBcc: true });
              }}
            >
              BCC
            </a>
          )}
          <FormGroup row>
            <Label for="subject" sm={2}>Subject</Label>
            <Col sm={10}>
            <Input
              type="text"
              label="subject"
              className="form-control-plaintext border-bottom"
              id="subject"
              placeholder="Subject"
              defaultValue={this.props.isEmailFwd ? 'Fwd: '+this.props.subject: 'Re: '+this.props.subject}
            />
            </Col>
          </FormGroup>

          </Form>
        </div>
        <span>&nbsp;</span>
        <Editor
          editorState={editorState}
          wrapperClassName="demo-wrapper"
          editorClassName="demo-editor"
          onEditorStateChange={this.onEditorStateChange}
          placeholder="Type your mail here..."
          toolbarClassName="toolbar-class"
          toolbar={{
            inline: { inDropdown: true },
            list: { inDropdown: true },
            textAlign: { inDropdown: true },
            link: { inDropdown: true },
            history: { inDropdown: true },
          }}
        />
        <div>  
          <MdAttachFile />
          &nbsp;
          <input
            type="file"
            name="attachment"
            id="attachment"
            multiple
            onChange={ (ev) => {
              this.props.handleAttachment(ev);
            }}
          />
          <p className="text-muted">Maximum allowed file size 20MB</p>
        </div>
        <RButton
          className="mr-2"
          onClick={() => {
            let email = {};
            let content = editorState.getCurrentContent();
            if(this.props.markdown){
              email = draftToMarkdown(convertToRaw(content));
            }else{
              email = draftToHtml(convertToRaw(content));
            }
            this.props.replyEmail(email);
          }}
        >
          Send
        </RButton>
        <RButton
          onClick={() => {
            document.getElementById("attachment").value='';
            this.props.editor();
            this.props.resetSubject();
            this.props.closeEditors();
          }}
        >
          <FaTrash />
        </RButton>
      </div>
    );
  }
}
export default MessageEditor;