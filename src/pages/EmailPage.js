import React from 'react';
import {
    FaTrash,
    FaArrowLeft,
    FaStar,
    FaRegStar,
    FaExclamationTriangle
  } from 'react-icons/fa';

  import {
    Button as RButton,
    Card,
    CardBody,
    CardHeader,
    Col,
    Row,
    NavLink,
  } from 'reactstrap';
  
import MessageEditor from '../components/MessageEditor';

const emailPage = (props) => {
    
    let floatDir = "float-right";
    if(props.breakpoint==="xs"){
      floatDir="float-left i-block";
    }

    

   return (
        <Row className="email-pg">
          <Col xl={9} lg={9} md={9} xs={9}>
            { props.thread.map((el,index) => {
              let emlBody = el.body;
             if(el.hasAttachments===1){
                emlBody+="<hr><p>Attachments:</p><ul>";
                let path = window.location.pathname;
                let arr = path.split("/");
                let segment = arr.pop();
                while(segment!=="cmail"){
                  segment = arr.pop();
                }
                
                path = arr.join("/");
            
                for(let i=0;i<el.attachment.length;i++){
                  emlBody+="<li><a href='"+path+el.attachment[i]['url']+"' target='_blank' download>"+el.attachment[i]['file']+"</a></li>";
                }
                emlBody+="</ul>";

              }
             return (<React.Fragment key={el.uid}>
            <Card className="mt-3">
              <CardHeader>
                  <div className="clearfix">
                    <NavLink 
                      to="#url"
                      title="Back to mailbox"
                      onClick={e => {
                        e.preventDefault();
                        props.setState({
                          page: 'list',
                        });
                        props.fetchEmails();
                      }}
                      className="email-icons float-left"
                    >
                        <FaArrowLeft />
                    </NavLink>
                    <span className="eml-subject float-left">{el.subject}</span>
                    
                  <NavLink 
                    to="#" 
                    className={`email-icons ${floatDir}`}
                    title="Delete email"
                      onClick={e => {
                        e.preventDefault();
                        let uid = el.uid;
                        props.trashEmail(uid);
                        props.setState({
                          page: 'list',
                        });
                        props.fetchEmails();
                      }}
                  >
                    <FaTrash />
                  </NavLink>
                  <NavLink 
                    to="#" 
                    className={`email-icons ${floatDir}`}
                    title="Mark email as spam"
                      onClick={e => {
                        e.preventDefault();
                        let uid = el.uid;
                        props.spamEmail(uid);
                        props.setState({
                          page: 'list',
                        });
                        props.fetchEmails();
                      }}
                  >
                    <FaExclamationTriangle />
                  </NavLink>
                  <NavLink 
                      to="#"
                      title={props.emailStarred ? "Unmark as starred": "Mark as starred"}
                      onClick={e => {
                          e.preventDefault();
                          let uid = el.uid;
                          let nextStarredState = 0;
                          
                          if(props.emailStarred===1){
                            nextStarredState = 0;
                          }else{
                            nextStarredState = 1;
                          }
                          
                          props.setState({
                            isEmailStarred: nextStarredState
                          });
  
                          props.starEmail(uid,nextStarredState);
                        }}
                      className={`email-icons ${floatDir}`}
                    >
                    
                      {props.emailStarred ? <FaStar />:<FaRegStar />} 
                    
                  </NavLink>
                  </div>
                  <div className="clearfix">
                    <span className="float-left eml-from" >{el.from}</span>
                    <span className={`${floatDir}`}>{props.displayDate(el.date)}</span>
                  </div>
              </CardHeader>
              <CardBody
                dangerouslySetInnerHTML={{
                  __html: `${emlBody}`
                }}
              />
            </Card>
            <Card className="mt-3">
              <CardBody>
                <Row className="editor-container">
                  {el['editor'] ? (
                    <Col xs="auto">
                      <MessageEditor
                        className="ql-editor"
                        editor={props.editorHandler}
                        from={el.from}
                        cc={el.cc}
                        bcc={el.bcc}
                        subject={el.subject}
                        replyEmail={props.replyEmail}
                        resetSubject={props.resetSubject}
                        orFrom={el.from}
                        orCc={el.cc}
                        orBcc={el.bcc}
                        to={el.to}
                        orTo={el.to}
                        uid={el.uid}
                        fwdMsg={props.fwdMsg}
                        isEmailFwd={props.isEmailFwd}
                        handleAttachment={ (ev) => { props.handleAttachment(ev) }}
                        closeEditors={props.closeEditors}
                        markdown={props.markdown}
                      />
                    </Col>
                  ) : (
                    <Row>
                      <div className="ml-3">
                        <RButton
                          className="ml-3"
                          onClick={() => {
                            props.activeThread(index);
                            let  subject = el.subject;
                            props.setState({
                              editor: true,
                              orSubject: subject,
                              subject: subject,
                              isEmailFwd: false,
                            });
                          }}
                        >
                          Reply
                        </RButton>
                      </div>
                      <div className="ml-3">
                        <RButton
                          className="ml-3"
                          onClick={() => {
                            props.activeThread(index);
                            let subject = el.subject;
                            props.setState({
                              editor: true,
                              orSubject: subject,
                              subject: subject,
                              isEmailFwd: true  
                            });
                          }}
                        >
                          Forward
                        </RButton>
                      </div>
                    </Row>
                  )}
                </Row>
              </CardBody>
            </Card>
            </React.Fragment>);
            })} 
          </Col>
        </Row>
      );
};

export default emailPage;