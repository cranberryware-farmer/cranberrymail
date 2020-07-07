import React from 'react';
import Page from 'components/Page';
import EnhancedTable from 'components/Cranberry/EnhancedTable';
import axios from 'axios';
import { withRouter } from 'react-router-dom';
import { Spinner } from 'reactstrap';
import { Container, Button } from 'react-floating-action-button';
import {
  FaPen,
  FaTools
} from 'react-icons/fa';
import {
  Button as CButton,
  Card,
  Row,
} from 'reactstrap';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

import { draftToMarkdown } from 'markdown-draft-js';
import { EditorState, convertToRaw } from 'draft-js';
import draftToHtml from 'draftjs-to-html';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';
import ComposeModal from './ComposeModal';
import EmailPage from './EmailPage';
import SettingsPage from './SettingsPage';
import {stateFromHTML} from 'draft-js-import-html';


function createData(id, starred, from, subject,body,attachment, timestamp) {
  return { id, starred, from, subject, timestamp };
}

/*const rows = [
  createData('1', 0, 'CranberryMail', 'Loading emails', 'Please wait for a moment...', 1, new Date().toLocaleString()),
];*/

const headCells = [
  { id: 'starred', numeric: true, disablePadding: false, label: '' },
  { id: 'from', numeric: false, disablePadding: false, label: 'From' },
  { id: 'subject', numeric: false, disablePadding: false, label: 'Subject' },
  { id: 'timestamp', numeric: false, disablePadding: false, label: 'Date' },
  /*{ id: 'body', numeric: false, disablePadding: false, label: 'Body' },
  {
    id: 'attachment',
    numeric: true,
    disablePadding: false,
    label: 'Attachment',
  },*/
  
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
    axios.defaults.headers.common['Authorization'] = props.location.state.token;
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

  resetSubject= () =>{
    this.setState({
      subject: this.state.orSubject
    });
  };

  replyEmail = (emailBody) => {
    
    let { token } = this.state.app;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };
    let body = emailBody;

    let cc =  document.getElementById('cc');
    let bcc = document.getElementById('bcc');
    let to = document.getElementById('staticEmail').value;
    let subject = document.getElementById('subject').value;
    let messageId = this.state.messageId;

    let data = {};
   
    if(this.state.attachment){
   
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
    }else{
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
            this.closeEditors();
            const currentFolder = this.props.curFolder;
            const currentFolderLower = currentFolder.toString().toLowerCase();
            if(currentFolderLower.match('draft') !== null){
              this.fetchEmails();
            }
        }else{
          toast(res.data.message);
        }
      })
      .catch(error => {
        console.log("Reply Email Unsuccessful", error);
      });
  };
  starEmail = (uid,emailState) => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      let { token } = this.state.app;
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + token,
        },
      };
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
          if(emailState===0){
            toast('Email moved to Inbox');
          }else{
            toast('Email moved to Starred');
          }

          if(this.state.page === 'list'){
            let rows = this.state.rows;
            rows = rows.filter((row) => {
              if(row.id===uid){
                return false;
              }else{
                return true;
              }    
            });

            this.setState({rows});
          }

        }else{
          if(emailState===0){
            toast('Email still marked as starred');
          }else{
            toast('Unable to mark email as starred');
          }
        }
      })
      .catch(error => {
        console.log("StarEmail Unsuccessful", error);
      });
    }
  };

  untrashEmail = (uid) => {
    let flag = 0;
    let { token } = this.state.app;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };

    if(Array.isArray(uid)){
      let rows = this.state.rows;
      uid.sort();
      
      let i = 0;
      rows = rows.filter((row) => {
          if(row.id===uid[i]){
            i++;
            return false;
          }else{
            return true;
          }    
      });

      this.setState({rows});
      uid = JSON.stringify(uid);
      flag = 1;
    }

    const data = {
      curfolder: 'inbox',
      trash: 'trash',
      uid
    };

    axios
    .post(window._api + '/untrash_emails', data, config)
    .then(res => {
      if (res.data.result > 0) {
        if(flag===1){
          toast("Emails have been restored");
        }else{
          toast('Email has been restored');
        }
        
      }
    })
    .catch(error => {
      console.log("Untrash Unsuccessful", error);
    });
  };

  trashEmail = (uid) => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      let flag = 0;
      let { token } = this.state.app;
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + token,
        },
      };

      if(Array.isArray(uid)){
        let rows = this.state.rows;
        uid.sort();
        
        let i = 0;
        rows = rows.filter((row) => {
            if(row.id===uid[i]){
              i++;
              return false;
            }else{
              return true;
            }    
        });

        this.setState({rows});
        uid = JSON.stringify(uid);
        flag = 1;
      }

      const data = {
        curfolder: this.props.curFolder,
        trash: 'trash',
        uid
      };

      axios
      .post(window._api + '/trash_emails', data, config)
      .then(res => {
        if (res.data.result > 0) {
          if(flag===1){
            toast("Emails have been deleted");
          }else{
            toast('Email has been deleted');
          }
        }
      })
      .catch(error => {
        console.log("TrashEmail Unsuccessful", error);
      });  
    }
    
  };

  spamEmail = (uid) => {
    if(this.props.curFolder!=='' || this.props.curFolder!==undefined){
      let flag = 0;
      let { token } = this.state.app;
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + token,
        },
      };

      if(Array.isArray(uid)){
        let rows = this.state.rows;
        uid.sort();
        
        let i = 0;
        rows = rows.filter((row) => {
            if(row.id===uid[i]){
              i++;
              return false;
            }else{
              return true;
            }    
        });

        this.setState({rows});
        uid = JSON.stringify(uid);
        flag = 1;
      }

      const data = {
        curfolder: this.props.curFolder,
        spam: 'spam',
        uid
      };

      axios
      .post(window._api + '/spam_emails', data, config)
      .then(res => {
        if (res.data.result > 0) {
          if(flag===1){
            toast("Emails marked as spam");
          }else{
            toast('Email marked as spam');
          }
        }
      })
      .catch(error => {
        console.log("SpamEmails Unsuccessful", error);
      }); 
    }
    
  };

  unspamEmail = (uid) => {
    let flag = 0;
    let { token } = this.state.app;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };

    if(Array.isArray(uid)){
      let rows = this.state.rows;
      uid.sort();
      
      let i = 0;
      rows = rows.filter((row) => {
          if(row.id===uid[i]){
            i++;
            return false;
          }else{
            return true;
          }    
      });

      this.setState({rows});
      uid = JSON.stringify(uid);
      flag = 1;
    }

    const data = {
      curfolder: 'inbox',
      spam: 'spam',
      uid
    };

    axios
    .post(window._api + '/unspam_emails', data, config)
    .then(res => {
      if (res.data.result > 0) {
        if(flag===1){
          toast("Emails have been restored");
        }else{
          toast('Email has been restored');
        }
      }
    })
    .catch(error => {
      console.log("Unspam Emails Unsuccessful", error);
    });
  };

  saveDraft = (e) => {
    let { token } = this.state.app;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };
    this.setState({
      savingDraft: true
    });

    let body = {};
    let content = this.state.modalEditorState.getCurrentContent();

    if(this.state.enableMarkdown){
      body = draftToMarkdown(convertToRaw(content));
    }else{
      body = draftToHtml(convertToRaw(content));
    }
    
    let cc =  document.getElementById('cc');
    let bcc = document.getElementById('bcc');
    let to = document.getElementById('to').value;
    let subject = document.getElementById('subject').value;

    let data = {};

    if(this.state.attachment){
      let i=0;
      data = new FormData();
    
      let fileData = this.state.attachment;
      
      for(;i<fileData.length;i++){
        data.append('attachment[]',fileData[i]);
      }
      
      data.append('to',to);
      data.append('subject',subject);
      data.append('body',body);

      if(cc !== null){
        data.append('cc',cc.value);
      }
      if(bcc!== null){
        data.append('bcc',bcc.value);
      }
      data.append('draft_id',this.state.draftID);

      if(this.state.attachmentURLs.length >0) {
        data.append('attachmentURLs',JSON.stringify(this.state.attachmentURLs));
      }
  
      config.headers['Content-Type']= 'multipart/form-data';
     
    }else{
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
        }else{
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

  sendEmail = (e) => {
    let { token } = this.state.app;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token,
      },
    };
    this.setState({
      isSending: true
    });

    let body = {};
    let content = this.state.modalEditorState.getCurrentContent();

    if(this.state.enableMarkdown){
      body = draftToMarkdown(convertToRaw(content));
    }else{
      body = draftToHtml(convertToRaw(content));
    }
    
    let cc =  document.getElementById('cc');
    let bcc = document.getElementById('bcc');
    let to = document.getElementById('to').value;
    let subject = document.getElementById('subject').value;

    let data = {};

    if(this.state.attachment){
      let i=0;
      data = new FormData();
    
      let fileData = this.state.attachment;
      
      for(;i<fileData.length;i++){
        data.append('attachment[]',fileData[i]);
      }
      
      data.append('to',to);
      data.append('subject',subject);
      data.append('body',body);

      if(cc !== null){
        data.append('cc',cc.value);
      }
      if(bcc!== null){
        data.append('bcc',bcc.value);
      }
      data.append('draft_id',this.state.draftID);
      if(this.state.attachmentURLs.length >0) {
        data.append('attachmentURLs',JSON.stringify(this.state.attachmentURLs));
      }
  
      config.headers['Content-Type']= 'multipart/form-data';
     
    }else{
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
            isSending: false
          });
          toast('Email has been sent');
        }else{
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
        attachment: false
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
  
  resizeDock = dockstate => {
    let cc =  document.getElementById('cc').value;
    let bcc = document.getElementById('bcc').value;
    let to = document.getElementById('to').value;
    let subject = document.getElementById('subject').value;
    let attachmentContent = document.getElementById('attachment').files;
    this.setState({
      dockState: dockstate,
      toCompose: to,
      ccCompose: cc,
      bccCompose: bcc,
      subjectCompose: subject,
      attachmentContent
    });
  };

  setEmailNode = node => {
    
    let cc = '';
    let bcc = '';
    let from = '';
    let to='';

    let orTo='';
    let orCc='';
    let orBcc='';
    let orFrom='';
    let body = '';
    let subject = '';

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

    let months_arr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      let date = new Date(node.date*1000);
      let year = date.getFullYear();
      let month = months_arr[date.getMonth()];
      let day = date.getDate();
      let hours = date.getHours();
      let minutes = "0" + date.getMinutes();
      date = day+'-'+month+'-'+year+' '+hours + ':' + minutes.substr(-2);

    fwdMsg+= "Date: "+date+"<br>";
    fwdMsg+= "Subject: "+subject+"<br>";
    fwdMsg+="To: "+to+"<br><br><br>";

    fwdMsg+=node.body;
    if(node.hasAttachments===1){
      fwdMsg+="<hr><ul>";
      let i=0;
      for(i=0;i<node.attachment.length;i++){
        fwdMsg+="<li><a href='"+window.location.origin+node.attachment[i]['url']+"' target='_blank'>"+node.attachment[i]['file']+"</a></li>";
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
    let threadMax = thread.length;
    
    for(let i=0;i<threadMax;i++){
      thread[i].editor = false;
    }

    this.setState({
      thread
    });


  };

  activeThread = index =>{
    let { thread } = this.state;
    let threadMax = thread.length;
    
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
      if(this.state.emailThreads[i].uids.indexOf(uid)>=0){
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
      
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + this.state.app.token,
        },
      };

      const data = {
        folder: currentFolder,
        thread_uids: this.state.emailThreads[row_index]['uids'],
        has_thread: 1
      };

      axios
        .post(window._api + '/email', data, config)
        .then(res => {
          if (res.status === 200 && res.data.length > 0) {
            if(currentFolderLower.match("draft") !== null){
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
          }else{
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
    if (this.props.location.state !== undefined) {
    } else {
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
      this.searchEmails(term);
    }
  }
 
  displayDate = (curDate) => {
    let months_arr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let date = new Date(curDate*1000);
    let year = date.getFullYear();
    let month = months_arr[date.getMonth()];
    let day = date.getDate();
    let hours = date.getHours();
    let minutes = "0" + date.getMinutes();
    date = day+'-'+month+'-'+year+' '+hours + ':' + minutes.substr(-2);

    let eDate= date;
   
    let result = `${eDate}`;
    return result;
  };

  fetchEmails = () => {
    if(this.props.curFolder!=='' && this.props.curFolder!==undefined){
      this.setState({
        busy: 1,
        rows: []
      });
    
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + this.state.app.token,
        },
      };
    
      const data = {
        folder: this.props.curFolder,
        has_thread: 1
      };
    
      axios
        .post(window._api + '/emails', data, config)
        .then(res => {
          if (res.status === 200) {
            
            let erows = [];
            let threads = [];
            let starred = 0;
            let curFolder = this.props.curFolder;
            curFolder = curFolder.toLowerCase();
            if(curFolder.search("starred") > 0){
              starred = 1;
            }
           
            for (let i = 0; i < res.data.length; i++) {
              let body = undefined; 
              let months_arr = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
              let date = new Date(res.data[i].date*1000);
              let year = date.getFullYear();
              let month = months_arr[date.getMonth()];
              let day = date.getDate();
              let hours = date.getHours();
              let minutes = "0" + date.getMinutes();
              let fdate = day+'-'+month+'-'+year+' '+hours + ':' + minutes.substr(-2);
    
              let isAttached = res.data[i].hasAttachments ? 1 : 0;
              erows[i] = createData(
                res.data[i].uid,
                starred,
                res.data[i].from,
                res.data[i].subject,
                body,
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
  
      const config = {
        headers: {
          Accept: 'application/json',
          Authorization: 'Bearer ' + this.state.app.token,
        },
      };
  
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
              let body = undefined;
              let fdate = new Date(res.data[i].date).toLocaleDateString('en-GB', {
                month: 'numeric',
                day: 'numeric',
                year: 'numeric',
              });
  
              let isAttached = res.data[i].hasAttachments ? 1 : 0;
              erows[i] = createData(
                res.data[i].uid,
                0,
                res.data[i].from[0].mail,
                res.data[i].subject,
                body,
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

  attachmentDownload = (file_name, part_id) => {
    const mailbox = this.props.curFolder;
    // const currentFolderLower = mailbox.toString().toLowerCase();
    if(mailbox !== '' || mailbox !== undefined) {
      const message_id = 0;
      const json_data = {
        file_name,
        part_id,
        message_id,
        mailbox
      };
      axios({
        method: 'post',
        url: window._api + '/download_attachment',
        data: json_data,
        responseType: 'stream'
      })
      .then(function (response) {
        console.log(response);
        // response.data.pipe(fs.createWriteStream('ada_lovelace.jpg'))
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
                  ></EnhancedTable>
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
