import React from 'react';
import Page from 'components/Page';
import EnhancedTable from 'components/Cranberry/EnhancedTable';
import axios from 'axios';
import { withRouter } from 'react-router-dom';
import { Button as CButton, Card, Row, Spinner } from 'reactstrap';
import { Button, Container } from 'react-floating-action-button';
import { FaPen, FaTools } from 'react-icons/fa';
import { toast, ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { draftToMarkdown } from 'markdown-draft-js';
import { convertToRaw, EditorState } from 'draft-js';
import draftToHtml from 'draftjs-to-html';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';
import ComposeModal from './ComposeModal';
import EmailPage from './EmailPage';
import SettingsPage from './SettingsPage';
import { stateFromHTML } from 'draft-js-import-html';

function createData(id, starred, from, subject,body,attachment, timestamp) {
  return { id, starred, from, subject, timestamp };
}

const headCells = [
  { id: 'starred', numeric: true, disablePadding: false, label: '' },
  { id: 'from', numeric: false, disablePadding: false, label: 'From' },
  { id: 'subject', numeric: false, disablePadding: false, label: 'Subject' },
  { id: 'timestamp', numeric: false, disablePadding: false, label: 'Date' },
];

class InboxPage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      app: props.location.state,
      rows: [],
      cells: headCells,
      title: '',
      busy: 1,
      modal: false,
      modal_backdrop: false,
      modal_nested_parent: false,
      modal_nested: false,
      backdrop: true,
      page: 'list',
      uid: '',
      orSubject:'',
      subject: '',
      body: '',
      to: '',
      orTo: '',
      orFrom: '',
      from: '',
      orCc: '',
      cc: '',
      orBcc:'',
      bcc: '',
      date: '',
      fwdMsg:'',
      content: '',
      attachmentContent: '',
      editor: false,
      modalEditorState: EditorState.createEmpty(),
      isEmailFwd: false,
      attachment: [],
      isEmailStarred: 0,
      thread: [], // thread of an email
      emailThreads: [], // threads of all emails
      messageId: '',
      enableMarkdown: false,
      enableEmailThread: false,
      dateFormat: '',
      dockState: "normal",
      isSending: false,
      toCompose: '',
      ccCompose: '',
      bccCompose: '',
      subjectCompose: '',
      savingDraft: false,
      draftID: 0,
      attachmentURLs: []
    };
    if(props.location.state !== undefined && props.location.state.hasOwnProperty('token')){
      axios.defaults.headers.common['Authorization'] = 'Bearer ' + props.location.state.token;
    }
  }

  handleAttachment = (ev) =>{
    const fileData = ev.target.files;
    this.setState({
      attachment: fileData
    });
  };

  updateState = (state) =>{
    this.setState({...state});
  };

  onModalEditorStateChange = modalEditorState => {
    this.setState({
      modalEditorState,
    });
  };

  editorHandler = () => {
    this.setState({
      editor: false,
    });
  };

  resetSubject= () => {
    this.setState({
      subject: this.state.orSubject
    });
  };

  getAxiosConfig = () => {
    const { token } = this.state.app;
    return {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };
  }

  mailBodyWithoutAttachment = body => {
    let config = this.getAxiosConfig();

    let data;
    const cc =  document.getElementById('cc');
    const bcc = document.getElementById('bcc');
    const to = document.getElementById('staticEmail').value;
    const subject = document.getElementById('subject').value;
    const messageId = this.state.messageId;

    if (this.state.attachment) {
      data = new FormData();

      let i=0;
      let fileData = this.state.attachment;

      for(;i<fileData.length;i++){
        data.append('attachment[]',fileData[i]);
      }

      data.append('to',to);
      data.append('subject',subject);
      data.append('body',body);
      data.append('messageId',messageId);
      if(cc !== null){
        data.append('cc',cc.value);
      }
      if(bcc!== null){
        data.append('cc',bcc.value);
      }
      config.headers['Content-Type']= 'multipart/form-data';
    } else {
      data = {
        to,
        subject,
        body,
        messageId
      };
      if(cc !== null){
        data.cc = cc.value;
      }
      if(bcc!== null){
        data.bcc = bcc.value;
      }
    }
    return {data, config};
  }

  replyEmail = (emailBody) => {
    const {data, config} = this.mailBodyWithoutAttachment(emailBody);

    axios
      .post(window._api + '/smtp/sendEmail', data, config)
      .then(res => {
        if (res.data.result > 0) {
          this.editorHandler();
          this.setState({
            attachment: false
          });
          toast('Email has been sent');
          this.resetSubject();
          this.closeModal();
          this.closeEditors();
          const currentFolder = this.props.curFolder;
          const currentFolderLower = currentFolder.toString().toLowerCase();
          if(currentFolderLower.match('draft') !== null){
            this.fetchEmails();
          }
        } else {
          toast(res.data.message);
        }
      })
      .catch(error => {
        console.log("Reply Email Unsuccessful", error);
      });
  };

  starEmail = (uid, emailState) => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      const config = this.getAxiosConfig();

      const data = {
        curFolder: this.props.curFolder,
        uid,
        emailState,
        trashFolder: "trash",
        starredFolder: "starred"
      };
      axios
        .post(window._api + '/star_emails', data, config)
        .then(res => {
          if (res.data.result > 0) {
            if(emailState === 0){
              toast('Email moved to Inbox');
            } else {
              toast('Email moved to Starred');
            }

            if (this.state.page === 'list') {
              let rows = this.state.rows;
              rows = rows.filter((row) => {
                return row.id !== uid;
              });
              this.setState({rows});
            }
          } else {
            if (emailState === 0) {
              toast('Email still marked as starred');
            } else {
              toast('Unable to mark email as starred');
            }
          }
        })
        .catch(error => {
          console.log("StarEmail Unsuccessful", error);
        });
    }
  };

  selectGridRows = uuid => {
    let flag = 0;
    if(Array.isArray(uuid)){
      let rows = this.state.rows;
      uuid.sort();

      let i = 0;
      rows = rows.filter((row) => {
        if (row.id === uuid[i]) {
          i++;
          return false;
        } else {
          return true;
        }
      });

      this.setState({rows});
      uuid = JSON.stringify(uuid);
      flag = 1;
    }
    return {'uid': uuid, flag};
  }

  untrashEmail = uuid => {
    const config = this.getAxiosConfig();
    const { uid, flag } = this.selectGridRows(uuid);
    const data = {
      curfolder: 'inbox',
      trash: 'trash',
      uid
    };

    axios
    .post(window._api + '/untrash_emails', data, config)
    .then(res => {
      if (res.data.result > 0) {
        if (flag === 1) {
          toast("Emails have been restored");
        } else {
          toast('Email has been restored');
        }
      }
    })
    .catch(error => {
      console.log("Untrash Unsuccessful", error);
    });
  };

  trashEmail = uuid => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){

      const config = this.getAxiosConfig();
      const { uid, flag } = this.selectGridRows(uuid);
      const data = {
        curfolder: this.props.curFolder,
        trash: 'trash',
        uid
      };

      axios
        .post(window._api + '/trash_emails', data, config)
        .then(res => {
          if (res.data.result > 0) {
            if (flag === 1) {
              toast("Emails have been deleted");
            } else {
              toast('Email has been deleted');
            }
          }
        })
        .catch(error => {
          console.log("TrashEmail Unsuccessful", error);
        });
    }

  };

  spamEmail = uuid => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      const config = this.getAxiosConfig();
      const { uid, flag } = this.selectGridRows(uuid);

      const data = {
        curfolder: this.props.curFolder,
        spam: 'spam',
        uid
      };

      axios
        .post(window._api + '/spam_emails', data, config)
        .then(res => {
          if (res.data.result > 0) {
            if (flag === 1) {
              toast("Emails marked as spam");
            } else {
              toast('Email marked as spam');
            }
          }
        })
        .catch(error => {
          console.log("SpamEmails Unsuccessful", error);
        });
    }
  };

  unspamEmail = uuid => {
    const config = this.getAxiosConfig();
    const { uid, flag } = this.selectGridRows(uuid);
    const data = {
      curfolder: 'inbox',
      spam: 'spam',
      uid
    };

    axios
      .post(window._api + '/unspam_emails', data, config)
      .then(res => {
        if (res.data.result > 0) {
          if (flag === 1) {
            toast("Emails have been restored");
          } else {
            toast('Email has been restored');
          }
        }
      })
      .catch(error => {
        console.log("Unspam Emails Unsuccessful", error);
      });
  };

  mailBodyWithAttachment = type => {
    let config = this.getAxiosConfig();

    if(type === 'draft') {
      this.setState({
        savingDraft: true
      });
    } else if( type === 'send') {
      this.setState({
        isSending: true
      });
    }

    let body;
    const content = this.state.modalEditorState.getCurrentContent();

    if (this.state.enableMarkdown) {
      body = draftToMarkdown(convertToRaw(content));
    } else {
      body = draftToHtml(convertToRaw(content));
    }

    const cc =  document.getElementById('cc');
    const bcc = document.getElementById('bcc');
    const to = document.getElementById('to').value;
    const subject = document.getElementById('subject').value;

    let data;

    if (this.state.attachment) {
      data = new FormData();
      const fileData = this.state.attachment;

      for(let i=0;i<fileData.length;i++){
        data.append('attachment[]',fileData[i]);
      }

      data.append('to', to);
      data.append('subject', subject);
      data.append('body', body);

      if(cc !== null){
        data.append('cc', cc.value);
      }
      if(bcc!== null){
        data.append('bcc', bcc.value);
      }
      data.append('draft_id', this.state.draftID);

      if(this.state.attachmentURLs.length >0) {
        data.append('attachmentURLs', JSON.stringify(this.state.attachmentURLs));
      }
      config.headers['Content-Type'] = 'multipart/form-data';
    } else {
      data = {
        to,
        subject,
        body,
      };
      if(cc !== null){
        data.cc = cc.value;
      }
      if(bcc!== null){
        data.bcc = bcc.value;
      }
      data.draft_id = this.state.draftID;
      if(this.state.attachmentURLs.length >0) {
        data.attachmentURLs = JSON.stringify(this.state.attachmentURLs);
      }
    }
    return {data, config};
  }

  saveDraft = () => {
    const { data, config } = this.mailBodyWithAttachment('draft');

    axios
      .post(window._api + '/save_draft', data, config)
      .then(res => {
        if (res.data.success) {
          this.setState({
            savingDraft: false,
            draftID: res.data.draft
          });
          const currentFolder = this.props.curFolder;
          const currentFolderLower = currentFolder.toString().toLowerCase();
          if(currentFolderLower.match('draft') !== null){
            this.fetchEmails();
          }
          toast('Email has been saved to draft');
        } else {
          this.setState({
            savingDraft: false
          });
          // toast(res.message);
        }
      })
      .catch(error => {
        console.log("Save Draft Unsuccessful", error);
      });
  };

  sendEmail = () => {
    const { data, config } = this.mailBodyWithAttachment('send');

    axios
      .post(window._api + '/smtp/sendEmail', data, config)
      .then(res => {
        if (res.data.result > 0) {
          const currentFolder = this.props.curFolder;
          const currentFolderLower = currentFolder.toString().toLowerCase();
          if(currentFolderLower.match('draft') !== null){
            this.fetchEmails();
          }
          this.resetFields();
          this.setState({
            modal: false,
            isSending: false,
            draftID: 0
          });
          toast('Email has been sent');
        } else {
          this.setState({
            isSending: false
          });
          toast(res.data.message);
        }
      })
      .catch(error => {
        console.log("Send Email Unsuccessful", error);
      });
  };

  resetFields = () => {
    if (document.getElementById('to')) {
      document.getElementById('to').value = '';
      document.getElementById('cc').value = '';
      document.getElementById('bcc').value = '';
      document.getElementById('subject').value = '';
      document.getElementById('attachment').value = '';
      this.setState({
        modalEditorState: EditorState.createEmpty(),
        attachment: false,
        draftID: 0
      });
    }
  };

  handleCompose = modalType => () => {
    if (!modalType) {
      this.setState({
        modal: !this.state.modal,
      });
      this.resetFields();
    }
  };

  closeModal = () =>{
    if (document.getElementById('to')){
      this.saveDraft();
    }
    this.setState({
      modal: false,
      dockState: 'normal',
      toCompose: '',
      ccCompose: '',
      bccCompose: '',
      subjectCompose: '',
      attachmentContent: '',
      draftID: 0,
      modalEditorState: EditorState.createEmpty(),
    });
  };

  resizeDock = dockState => {
    const ccCompose =  document.getElementById('cc').value;
    const bccCompose = document.getElementById('bcc').value;
    const toCompose = document.getElementById('to').value;
    const subjectCompose = document.getElementById('subject').value;
    const attachmentContent = document.getElementById('attachment').files;
    this.setState({
      dockState,
      toCompose,
      ccCompose,
      bccCompose,
      subjectCompose,
      attachmentContent
    });
  };

  renderDateFormatted = givenDate => {
    const months_arr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const year = givenDate.getFullYear();
    const month = months_arr[givenDate.getMonth()];
    const day = givenDate.getDate();
    const hours = givenDate.getHours();
    const minutes = "0" + givenDate.getMinutes();
    return day+'-'+month+'-'+year+' '+hours + ':' + minutes.substr(-2);
  }

  setEmailNode = node => {
    let cc = '',
      bcc = '',
      from = '',
      to = '',
      orTo = '',
      orCc = '',
      orBcc = '',
      orFrom = '',
      body = '',
      subject = '';

    if(node){
      cc= node.cc;
      orCc = node.cc;

      bcc = node.bcc;
      orBcc = node.bcc;

      from = node.from;
      orFrom = node.from;

      to= node.to;
      orTo = node.to;

      body = node.body;
      subject = node.subject;
      let fwdMsg = "<br><br><br>===Previous Message===<br><br>";
      fwdMsg += "From: "+from+"<br>";

      if(cc.length > 5){
        fwdMsg += "Cc: "+cc+"<br>";
      }

      if(bcc.length > 5){
        fwdMsg += "Bcc: "+bcc+"<br>";
      }

      const date = this.renderDateFormatted(new Date(node.date*1000));

      fwdMsg+= "Date: "+date+"<br>";
      fwdMsg+= "Subject: "+subject+"<br>";
      fwdMsg+="To: "+to+"<br><br><br>";

      fwdMsg+=node.body;
      if(node.hasAttachments===1){
        fwdMsg+="<hr><ul>";
        for(let i=0;i<node.attachment.length;i++){
          fwdMsg+="<li>" +
            "<a href='"+window.location.origin+node.attachment[i]['url']+"' target='_blank'>" +
              node.attachment[i]['file'] +
            "</a>" +
          "</li>";
        }
        fwdMsg+="</ul>";
      }

      this.setState({
        page: 'email',
        subject,
        body,
        orFrom,
        from,
        orTo,
        to,
        orCc,
        cc,
        orBcc,
        bcc,
        date,
        busy: 0,
        fwdMsg,
        uid: node.uid,
        messageId: node.messageId
      });
    }
  };

  closeEditors = () => {
    let { thread } = this.state;
    const threadMax = thread.length;

    for(let i=0;i<threadMax;i++){
      thread[i].editor = false;
    }

    this.setState({
      thread
    });
  };

  activeThread = index =>{
    let { thread } = this.state;
    const threadMax = thread.length;

    for(let i=0;i<threadMax;i++){
      thread[i].editor = false;
    }

    thread[index].editor = true;

    this.setEmailNode(thread[index]);
  };

  getRowIndex = (uid) => {
    let { rows } = this.state;
    let max = rows.length;
    let i =0;
    for(;i<max;i++){
      if (this.state.emailThreads[i].uids.indexOf(uid) >= 0) {
        break;
      }
    }
    return i;
  };

  showSettings = () => {
    this.setState({
      page: "settings"
    });
  };

  showEmail = uid => {
    const currentFolder = this.props.curFolder;
    const currentFolderLower = currentFolder.toString().toLowerCase();
    if(currentFolder !== '' || currentFolder !== undefined){
      let row_index = this.getRowIndex(uid);
      window.scrollTo(0, 0);

      if(currentFolderLower.match("draft") === null) {
        this.setState({
          busy: 1,
          rows: [],
        });
      }

      const config = this.getAxiosConfig();
      const data = {
        folder: currentFolder,
        thread_uids: this.state.emailThreads[row_index]['uids'],
        has_thread: 1
      };

      axios
        .post(window._api + '/email', data, config)
        .then(res => {
          if (res.status === 200 && res.data.length > 0) {
            if (currentFolderLower.match("draft") !== null) {
              const {body, to, uid, cc, bcc, subject, attachment} = res.data[0];
              this.setState({
                modal: true,
                dockState: 'normal',
                toCompose: to,
                ccCompose: cc,
                bccCompose: bcc,
                subjectCompose: subject,
                modalEditorState: EditorState.createWithContent(stateFromHTML(body)),
                draftID: uid,
                attachmentURLs: attachment
              });
            } else {
              this.setEmailNode(res.data[0]);
              this.setState({
                "thread" : res.data
              });
              this.closeEditors();
            }
          } else {
            this.setState({
              page: 'list',
            });
            this.fetchEmails();
          }
        })
        .catch(error => {
          console.log("Email Content Fetch Unsuccessful", error);
        });
    }
  };

  componentDidMount() {
    if (this.props.location.state === undefined) {
      this.props.history.push('/login');
    }
  }
  componentDidUpdate(prevProps) {
    if (this.props.curFolder !== prevProps.curFolder) {
      this.setState({
        page: 'list',
        title: this.props.curFolder
      });
      this.fetchEmails();
    }

    if(this.props.searchTerm !== prevProps.searchTerm){
      this.setState({
        page: 'list',
      });

      let term = this.props.searchTerm;
      if(term) {
        this.searchEmails(term);
      } else {
        this.fetchEmails();
      }
    }
  }

  displayDate = (curDate) => {
    const date = this.renderDateFormatted(new Date(curDate*1000));
    return `${date}`;
  };

  fetchEmails = () => {
    if(this.props.curFolder!=='' && this.props.curFolder!==undefined){
      this.setState({
        busy: 1,
        rows: []
      });

      const config = this.getAxiosConfig();
      const data = {
        folder: this.props.curFolder,
        has_thread: 1
      };

      axios
        .post(window._api + '/emails', data, config)
        .then(res => {
          if (res.status === 200) {
            let erows = [],
              threads = [],
              starred = 0,
              curFolder = this.props.curFolder.toString().toLowerCase();
            if(curFolder.search("starred") > 0){
              starred = 1;
            }

            for (let i = 0; i < res.data.length; i++) {
              const fdate = this.renderDateFormatted(new Date(res.data[i].date*1000));
              const isAttached = res.data[i].hasAttachments ? 1 : 0;

              erows[i] = createData(
                res.data[i].uid,
                starred,
                res.data[i].from,
                res.data[i].subject,
                undefined, //body
                isAttached,
                fdate,
              );
             threads[i] = res.data[i].thread;
            }

            if (erows.length > 0) {
              this.setState({
                rows: erows,
                emailThreads: threads,
                busy: 0,
              });
            } else {
              this.setState({
                rows: '',
                emailThreads: [],
                busy: 0,
              });
            }
          }
        })
        .catch(error => {
          console.log("Email Threads fetch Unsuccessful", error);
        });
    }

  };

  searchEmails = (term) => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      this.setState({
        busy: 1,
        rows: []
      });

      const config = this.getAxiosConfig();
      const data = {
        curfolder: this.props.curFolder,
        sterm: term
      };

      axios
        .post(window._api + '/search_emails', data, config)
        .then(res => {
          if (res.status === 200) {
            let erows = [];
            let threads = [];
            for (let i = 0; i < res.data.length; i++) {
              const fdate = new Date(res.data[i].date).toLocaleDateString('en-GB', {
                month: 'numeric',
                day: 'numeric',
                year: 'numeric',
              });
              const isAttached = res.data[i].hasAttachments ? 1 : 0;

              erows[i] = createData(
                res.data[i].uid,
                0,
                res.data[i].from[0].mail,
                res.data[i].subject,
                undefined, //body
                isAttached,
                fdate,
              );
              threads[i] = res.data[i].threads;
            }

            if (erows.length > 0) {
              this.setState({
                rows: erows,
                emailThreads: threads,
                busy: 0,
              });
            } else {
              this.setState({
                rows: '',
                emailThreads: threads,
                busy: 0,
              });
            }
          }
        })
        .catch(error => {
          console.log("Email Search Unsuccessful", error);
        });
    }

  };

  handleRefresh = () => {
    this.fetchEmails();
  };

  attachmentDownload = (file_name, part_id, mail_uid) => {
    const mailbox = this.props.curFolder;
    // const currentFolderLower = mailbox.toString().toLowerCase();
    if(mailbox !== '' || mailbox !== undefined) {
      const message_id = 0;
      const json_data = {
        file_name,
        part_id,
        message_id,
        mailbox,
        mail_uid
      };
      axios({
        method: 'post',
        url: window._api + '/download_attachment',
        data: json_data,
        responseType: 'blob'
      })
      .then(function (response) {
        const element = document.createElement("a");
        const file = new Blob([response.data], {type: response.headers['content-type']});
        const blobURL = URL.createObjectURL(file);
        element.href = blobURL;
        element.download = file_name;
        document.body.appendChild(element);
        element.click();
        setTimeout(function() {
          document.body.removeChild(element);
          window.URL.revokeObjectURL(blobURL);
        }, 200);
        // response.data.pipe(console.log(response));
      })
      .catch(error => {
        console.log("Attachment Download Unsuccessful", error);
      });
    }
  }

  render() {
    return (
      <React.Fragment>
        <Row>
          <ToastContainer />
        </Row>
        <Page className="cm-inbox-page">
          {(this.state.page === 'list' || this.props.page === 'list') && (
            <React.Fragment>
              {this.state.rows.length === 0 && this.state.busy === 0 && (
                <p style={{ padding: 15 }}>No emails found</p>
              )}

              {this.state.busy === 1 && (
                <div className="cr-page-spinner">
                  <Spinner
                    color={'danger'}
                    style={{
                      width: '10rem',
                      height: '10rem',
                      marginTop: '10rem',
                    }}
                  />{' '}
                </div>
              )}

              {this.state.rows.length !== 0 && (
                <React.Fragment>
                  <Card className="email-refresh">
                    <div>
                      <CButton onClick={this.showSettings}>
                        <FaTools />
                      </CButton>
                    </div>
                  </Card>
                  <EnhancedTable
                    rows={this.state.rows}
                    rowsLength={this.state.rows.length}
                    headCells={this.state.cells}
                    tableTitle={this.props.curFolder}
                    labelRowsPerPage="Per page"
                    showEmptyRows={false}
                    props={this.props}
                    showEmail={this.showEmail}
                    trashEmail={this.trashEmail}
                    untrashEmail={this.untrashEmail}
                    starEmail = {this.starEmail}
                    spamEmail = {this.spamEmail}
                    unspamEmail = {this.unspamEmail}
                    breakpoint = {this.props.breakpoint}
                  >
                  </EnhancedTable>
                </React.Fragment>
              )}

              {this.state.modal && <ComposeModal
                                      modal = {this.state.modal}
                                      dockState = {this.state.dockState}
                                      to = {this.state.toCompose}
                                      cc = {this.state.ccCompose}
                                      bcc = {this.state.bccCompose}
                                      subject = {this.state.subjectCompose}
                                      isSending = {this.state.isSending}
                                      attachmentContent = {this.state.attachmentContent}
                                      composehandler = {this.handleCompose}
                                      closemodal = {this.closeModal}
                                      resizeDock = {this.resizeDock}
                                      classnm = {this.props.className}
                                      sendemail = {this.sendEmail}
                                      saveDraft = {this.saveDraft}
                                      meditorstate = {this.state.modalEditorState}
                                      meditorstatehandler= {this.onModalEditorStateChange}
                                      attachmenthandler = {this.handleAttachment}
                                      markdown = {this.state.enableMarkdown}
                                    />}
            </React.Fragment>
          )}
          {this.state.busy === 0 && this.state.page === 'list' && (
            <Container>
              <Button
                tooltip="Compose email"
                rotate={false}
                onClick={this.handleCompose()}
              >
                <FaPen />
              </Button>
            </Container>
          )}
          {this.state.page === 'email' && <EmailPage
                                            breakpoint = {this.state.breakpoint}
                                            thread = { this.state.thread }
                                            setState = {this.updateState}
                                            fetchEmails = {this.fetchEmails}
                                            trashEmail = {this.trashEmail}
                                            spamEmail = {this.spamEmail}
                                            emailStarred = {this.state.isEmailStarred}
                                            starEmail = {this.starEmail}
                                            displayDate={this.displayDate}
                                            editor = {this.state.editor}
                                            editorHandler = {this.editorHandler}
                                            replyEmail = {this.replyEmail}
                                            resetSubject={this.resetSubject}
                                            fwdMsg={this.state.fwdMsg}
                                            isEmailFwd={this.state.isEmailFwd}
                                            handleAttachment={this.handleAttachment}
                                            activeThread = {this.activeThread}
                                            closeEditors = {this.closeEditors}
                                            markdown={this.state.enableMarkdown}
                                            attachmentDownload={this.attachmentDownload}
                                          />}
          {this.state.page === 'settings' && <SettingsPage
                                              breakpoint = {this.state.breakpoint}
                                              setState = {this.updateState}
                                              fetchEmails = {this.fetchEmails}
                                              markdown = {this.state.enableMarkdown}
                                            />}
        </Page>
      </React.Fragment>
    );
  }
}

export default withRouter(InboxPage);
