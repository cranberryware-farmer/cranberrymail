import React from 'react';
import { MdSearch } from 'react-icons/md';
import { Form, Input } from 'reactstrap';

const SearchInput = (props) => {
  return (
    <Form inline className="cr-search-form" onSubmit={e => {
      e.preventDefault();
      let term = document.getElementById('search').value;
      props.search(term);
    }}
    >
      <MdSearch
        size="20"
        className="cr-search-form__icon-search text-secondary"
      />
      <Input
        type="search"
        className="cr-search-form__input"
        id="search"
        placeholder="Search..."
      />
    </Form>
  );
};

export default SearchInput;
