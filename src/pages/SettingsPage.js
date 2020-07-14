import React from 'react';
import {
  Button,
  Card,
  CardBody,
  CardHeader,
  Col,
  Row,
  CustomInput,
} from 'reactstrap';

const settingsPage = (props) => {
  let markdownSwitch = props.markdown;
  return (
    <Row className="email-pg">
      <Col xl={9} lg={9} md={9} xs={9}>
        <Card className="mt-3">
          <CardHeader>
            Settings
          </CardHeader>
          <CardBody>
            <CustomInput
              type="switch"
              id="markdown"
              name="markdown"
              label="Enable Markdown"
              onChange={(ev) => {
                markdownSwitch = ev.target.checked;
              }}
              defaultChecked={props.markdown}
            />
            <br />
            <Button 
              onClick={()=>{
                props.setState({
                  enableMarkdown: markdownSwitch,
                  page: 'list'
                });
                props.fetchEmails();
              }}
            >
              Save
            </Button>
          </CardBody>
        </Card>
      </Col>
    </Row>
  );
};

export default settingsPage;