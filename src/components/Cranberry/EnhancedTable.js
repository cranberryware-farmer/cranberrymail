import React,{ useEffect } from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import { lighten, makeStyles } from '@material-ui/core/styles';
import Grid from '@material-ui/core/Grid';
import Table from '@material-ui/core/Table';
import TableBody from '@material-ui/core/TableBody';
import TableCell from '@material-ui/core/TableCell';
import TableHead from '@material-ui/core/TableHead';
import TablePagination from '@material-ui/core/TablePagination';
import TableRow from '@material-ui/core/TableRow';
import TableSortLabel from '@material-ui/core/TableSortLabel';
import Toolbar from '@material-ui/core/Toolbar';
import Typography from '@material-ui/core/Typography';
import Paper from '@material-ui/core/Paper';
import Checkbox from '@material-ui/core/Checkbox';
import IconButton from '@material-ui/core/IconButton';
import Tooltip from '@material-ui/core/Tooltip';
import FormControlLabel from '@material-ui/core/FormControlLabel';
import Switch from '@material-ui/core/Switch';
import { FaTrashRestore, FaTrash, FaExclamationTriangle, FaInbox } from "react-icons/fa";

function desc(a, b, orderBy) {
  if (b[orderBy] < a[orderBy]) {
    return -1;
  }
  if (b[orderBy] > a[orderBy]) {
    return 1;
  }
  return 0;
}

function stableSort(array, cmp) {
  const stabilizedThis = array.map((el, index) => [el, index]);
  stabilizedThis.sort((a, b) => {
    const order = cmp(a[0], b[0]);
    if (order !== 0) return order;
    return a[1] - b[1];
  });
  return stabilizedThis.map(el => el[0]);
}

function getSorting(order, orderBy) {
  return order === 'desc'
    ? (a, b) => desc(a, b, orderBy)
    : (a, b) => -desc(a, b, orderBy);
}

function EnhancedTableHead(props) {
  const {
    classes,
    onSelectAllClick,
    order,
    orderBy,
    numSelected,
    rowCount,
    headCells,
    onRequestSort,
  } = props;
  const createSortHandler = property => event => {
    onRequestSort(event, property);
  };

  return (
    <TableHead>
      <TableRow>
        <TableCell padding="checkbox">
          <Checkbox
            indeterminate={numSelected > 0 && numSelected < rowCount}
            checked={numSelected === rowCount}
            onChange={onSelectAllClick}
            inputProps={{ 'aria-label': 'select all desserts' }}
          />
        </TableCell>
        {headCells.map(headCell => (
          <TableCell
            key={headCell.id}
            align={headCell.numeric ? 'right' : 'left'}
            padding={headCell.disablePadding ? 'none' : 'default'}
            sortDirection={orderBy === headCell.id ? order : false}
          >
            <TableSortLabel
              active={orderBy === headCell.id}
              direction={order}
              onClick={createSortHandler(headCell.id)}
            >
              {headCell.label}
              {orderBy === headCell.id ? (
                <span className={classes.visuallyHidden}>
                  {order === 'desc' ? 'sorted descending' : 'sorted ascending'}
                </span>
              ) : null}
            </TableSortLabel>
          </TableCell>
        ))}
      </TableRow>
    </TableHead>
  );
}

EnhancedTableHead.propTypes = {
  classes: PropTypes.object.isRequired,
  numSelected: PropTypes.number.isRequired,
  onRequestSort: PropTypes.func.isRequired,
  onSelectAllClick: PropTypes.func.isRequired,
  order: PropTypes.oneOf(['asc', 'desc']).isRequired,
  orderBy: PropTypes.string.isRequired,
  rowCount: PropTypes.number.isRequired,
  headCells: PropTypes.object.isRequired,
};

const useToolbarStyles = makeStyles(theme => ({
  root: {
    paddingLeft: theme.spacing(2),
    paddingRight: theme.spacing(1),
  },
  highlight:
    theme.palette.type === 'light'
      ? {
          color: theme.palette.secondary.main,
          backgroundColor: lighten(theme.palette.secondary.light, 0.85),
        }
      : {
          color: theme.palette.text.primary,
          backgroundColor: theme.palette.secondary.dark,
        },
  spacer: {
    flex: '1 1 100%',
  },
  actions: {
    color: theme.palette.text.secondary,
  },
  title: {
    flex: '0 0 auto',
  },
}));

const EnhancedTableToolbar = props => {
  const classes = useToolbarStyles();
  const { numSelected, tableTitle } = props;

  const curFolder = (tableTitle !== undefined) ? tableTitle.toLowerCase(): '';

  return (
    <Toolbar
      className={clsx(classes.root, {
        [classes.highlight]: numSelected > 0,
      })}
    >
      <div className={classes.title}>
        {numSelected > 0 ? (
          <Typography color="inherit">{numSelected} selected</Typography>
        ) : (
          <Typography id="tableTitle">{tableTitle}</Typography>
        )}
      </div>
      <div className={classes.spacer} />
      <div className={classes.actions}>
        {numSelected > 0 && (
          <React.Fragment>
            {(curFolder.match("spam") === null) ? <Tooltip title="Mark as spam">
              <IconButton
                aria-label="spam"
                onClick = {
                  () => {
                    props.unselectAll();
                    props.spamEmails(props.rows);
                  }
                }
              >
              <FaExclamationTriangle />
              </IconButton>
              </Tooltip>:<Tooltip title="Not Spam">
                <IconButton
                  aria-label="unspam"
                  onClick = {
                    () => {
                      props.unselectAll();
                      props.unspamEmails(props.rows);
                    }
                  }
                >
                  <FaInbox />
                </IconButton>
              </Tooltip>
            }
            {(curFolder.match("trash") === null) ? <Tooltip title="Move to trash">
                <IconButton
                  aria-label="delete"
                  onClick = {
                    () => {
                      props.unselectAll();
                      props.trashEmails(props.rows);
                    }
                  }
                >
                  <FaTrash />
                </IconButton>
              </Tooltip>:<React.Fragment>
                <Tooltip title="Delete emails">
                  <IconButton
                    aria-label="delete"
                    onClick = {
                      () => {
                        props.unselectAll();
                        props.trashEmails(props.rows);
                      }
                    }
                  >
                    <FaTrash />
                  </IconButton>
                </Tooltip>
                <Tooltip title="Restore emails">
                  <IconButton
                    aria-label="undelete"
                    onClick = {
                      () => {
                        props.unselectAll();
                        props.untrashEmails(props.rows);
                      }
                    }
                  >
                    <FaTrashRestore />
                  </IconButton>
                </Tooltip>
              </React.Fragment>
            }
          </React.Fragment>
        )}
      </div>
    </Toolbar>
  );
};

EnhancedTableToolbar.propTypes = {
  numSelected: PropTypes.number.isRequired,
  tableTitle: PropTypes.string.isRequired,
};

const useStyles = makeStyles(theme => ({
  root: {
    width: '100%',
    // marginTop: theme.spacing(3),
  },
  paper: {
    width: '100%',
    // marginBottom: theme.spacing(2),
  },
  table: {
    minWidth: 750,
  },
  tableWrapper: {
    overflowX: 'auto',
  },
  visuallyHidden: {
    border: 0,
    clip: 'rect(0 0 0 0)',
    height: 1,
    margin: -1,
    overflow: 'hidden',
    padding: 0,
    position: 'absolute',
    top: 20,
    width: 1,
  },
}));

const EnhancedTable = ({
  rows,
  headCells,
  tableTitle,
  labelRowsPerPage,
  showEmptyRows,
  props,
  showEmail,
  trashEmail,
  starEmail,
  untrashEmail,
  spamEmail,
  unspamEmail,
  breakpoint
}) => {
  const classes = useStyles();
  const [order, setOrder] = React.useState('desc');
  const [orderBy, setOrderBy] = React.useState('id');
  const [selected, setSelected] = React.useState([]);
  const [page, setPage] = React.useState(0);
  const [dense, setDense] = React.useState(false);

  const [rowsPerPage, setRowsPerPage] = React.useState(30);
  labelRowsPerPage = labelRowsPerPage ? labelRowsPerPage : 'Per page';
  showEmptyRows = typeof showEmptyRows == 'undefined' ? true : showEmptyRows;

  const handleRequestSort = (event, property) => {
    const isDesc = orderBy === property && order === 'desc';
    setOrder(isDesc ? 'asc' : 'desc');
    setOrderBy(property);
  };

  const unselectAll = ev =>{
      setSelected([]);
  };

  const handleSelectAllClick = event => {
    if (event.target.checked) {
      const newSelecteds = rows.map(n => n.id);
      setSelected(newSelecteds);
      return;
    }
    setSelected([]);
  };

  const openEmail = (event, uid) => {
    showEmail(uid);
  };

  const handleClick = (event, id) => {
    const selectedIndex = selected.indexOf(id);
    let newSelected = [];

    if (selectedIndex === -1) {
      newSelected = newSelected.concat(selected, id);
    } else if (selectedIndex === 0) {
      newSelected = newSelected.concat(selected.slice(1));
    } else if (selectedIndex === selected.length - 1) {
      newSelected = newSelected.concat(selected.slice(0, -1));
    } else if (selectedIndex > 0) {
      newSelected = newSelected.concat(
        selected.slice(0, selectedIndex),
        selected.slice(selectedIndex + 1),
      );
    }

    setSelected(newSelected);
  };

  const toggleStar = (e, id) => {
    e.preventDefault();
    let el = document.querySelector('#star-' + id);
    let sFlag = el.getAttribute('data-starred');

    if (sFlag === '1') {
      starEmail(id,0);
    } else {
      starEmail(id,1);
    }
  };

  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = event => {
    setRowsPerPage(+event.target.value);
    setPage(0);
  };

  const handleChangeDense = event => {
    setDense(event.target.checked);
  };

  const isSelected = id => selected.indexOf(id) !== -1;

  const emptyRows =
    rowsPerPage - Math.min(rowsPerPage, rows.length - page * rowsPerPage);

  useEffect(() => {
      if(breakpoint==='xs'){
        setDense(true);
      }
  });

  return (
    <div className={classes.root}>
      <Paper className={classes.paper}>
        <Grid
          container
          direction="row"
          justify="space-between"
          alignItems="center"
          spacing={0}
          className={classes.root}
          style={{ borderBottom: '1px solid inherit' }}
        >
          <Grid item xs style={{ paddingLeft: 4 }}>
            <Checkbox
              indeterminate={
                selected.length > 0 && selected.length < rows.length
              }
              checked={selected.length === rows.length}
              onChange={handleSelectAllClick}
              inputProps={{ 'aria-label': 'select all' }}
            />
          </Grid>
          <Grid item xs>
            <EnhancedTableToolbar
              numSelected={selected.length}
              tableTitle={tableTitle}
              rows={selected}
              trashEmails={trashEmail}
              unselectAll={unselectAll}
              untrashEmails={untrashEmail}
              spamEmails={spamEmail}
              unspamEmails={unspamEmail}
            />
          </Grid>
          <Grid item xs>
            <TablePagination
              rowsPerPageOptions={[30, 50, 100]}
              labelRowsPerPage={labelRowsPerPage}
              component="div"
              count={rows.length}
              rowsPerPage={rowsPerPage}
              page={page}
              backIconButtonProps={{
                'aria-label': 'previous page',
              }}
              nextIconButtonProps={{
                'aria-label': 'next page',
              }}
              onChangePage={handleChangePage}
              onChangeRowsPerPage={handleChangeRowsPerPage}
            />
          </Grid>
        </Grid>
        <div className={classes.tableWrapper}>
          <Table
            className={classes.table}
            aria-labelledby="tableTitle"
            size={dense ? 'small' : 'medium'}
            striped
          >
            <TableBody>
              {stableSort(rows, getSorting(order, orderBy))
                .slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage)
                .map((row, index) => {
                  const isItemSelected = isSelected(row.id);
                  const labelId = `enhanced-table-checkbox-${index}`;
                  if(breakpoint==='xs'){
                    return(
                      <React.Fragment>
                        <TableRow
                          hover
                          role="checkbox"
                          aria-checked={isItemSelected}
                          tabIndex={-1}
                          key={row.id}
                          selected={isItemSelected}
                          className="hl-tr"
                        >
                          <TableCell
                            padding="checkbox"
                            onClick={event => handleClick(event, row.id)}
                            rowSpan={5}
                          >
                            <Checkbox
                              checked={isItemSelected}
                              inputProps={{ 'aria-labelledby': labelId }}
                            />
                          </TableCell>
                        </TableRow>
                        {Object.keys(row).map((row_key, row_key_index) => {
                          if (row_key !== 'id') {
                            if (row_key === 'starred') {
                              let ri = <TableCell>
                                          <span
                                            id={'star-' + row.id}
                                            onClick={e => toggleStar(e, row.id)}
                                            data-starred={row[row_key]}
                                            className="starred"
                                          >
                                            {row[row_key] === 1 ? '\u2605' : '\u2606'}
                                          </span>
                                        </TableCell>;
                              return (
                                <TableRow>
                                  {ri}
                                </TableRow>
                              );
                            } else if (row_key === 'subject') {
                              let maxText=30;
                              let ri = <TableCell
                                          onClick={event => openEmail(event,row.id)}
                                        >
                                          {row[row_key].substr(0, maxText)}...
                                        </TableCell>;
                              return (
                                <TableRow>
                                  {ri}
                                </TableRow>
                              );
                            } else if (row_key === 'attachment') {
                              /*return (
                                <TableRow>
                                <TableCell>
                                  {row[row_key] === 1 ? '\u1F4CE' : ''}
                                </TableCell>
                                </TableRow>
                              );*/
                            }else if(row_key==='timestamp'){
                              let date = row[row_key].split(" ");
                              date = date[0];
                              let ri = <TableCell
                                          onClick={event => openEmail(event,row.id)}
                                        >
                                          {date}
                                        </TableCell>;
                              return (
                                <TableRow>
                                  {ri}
                                </TableRow>
                              );
                            }else{
                              let ri = <TableCell
                                          onClick={event => openEmail(event, row.id)}
                                        >
                                          <strong>{row[row_key]}</strong>
                                        </TableCell>;

                              return (
                                <TableRow>
                                  {ri}
                                </TableRow>
                              );
                            }
                          }
                          return null;
                        })}
                      </React.Fragment>
                    );
                  }else{
                    return(
                      <TableRow
                        hover
                        role="checkbox"
                        aria-checked={isItemSelected}
                        tabIndex={-1}
                        key={row.id}
                        selected={isItemSelected}
                        className="hl-tr"
                      >
                        <TableCell
                          padding="checkbox"
                          onClick={event => handleClick(event, row.id)}
                          rowSpan={1}
                        >
                          <Checkbox
                            checked={isItemSelected}
                            inputProps={{ 'aria-labelledby': labelId }}
                          />
                        </TableCell>

                        {Object.keys(row).map((row_key, row_key_index) => {
                          if (row_key !== 'id') {
                            if (row_key === 'starred') {
                              let ri = <TableCell>
                                          <span
                                            id={'star-' + row.id}
                                            onClick={e => toggleStar(e, row.id)}
                                            data-starred={row[row_key]}
                                            className="starred"
                                          >
                                            {row[row_key] === 1 ? '\u2605' : '\u2606'}
                                          </span>
                                        </TableCell>;
                              return ri;
                            } else if (row_key === 'subject') {
                              let ri = <TableCell
                                          onClick={event => openEmail(event,row.id)}
                                        >
                                          {row[row_key].substr(0, 40)}...
                                        </TableCell>;
                              return ri;
                            } else if (row_key === 'attachment') {
                              /*return (
                                <TableRow>
                                <TableCell>
                                  {row[row_key] === 1 ? '\u1F4CE' : ''}
                                </TableCell>
                                </TableRow>
                              );*/
                            }else if(row_key==='timestamp'){
                              let date = row[row_key];
                              let ri = <TableCell
                                          onClick={event => openEmail(event,row.id)}
                                        >
                                          {date}
                                        </TableCell>;
                              return ri;
                            }else{
                              let ri = <TableCell
                                          onClick={event => openEmail(event,row.id)}
                                        >
                                          {row[row_key]}
                                        </TableCell>;
                              return ri;
                            }
                          }
                          return null;
                        })}
                      </TableRow>
                  );
                }
            })}
              {emptyRows > 0 && showEmptyRows && (
                <TableRow
                  key="t1"
                  style={{ height: (dense ? 33 : 53) * emptyRows }}
                >
                  <TableCell colSpan={headCells.length + 1} />
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
        <TablePagination
          rowsPerPageOptions={[30, 50, 100]}
          labelRowsPerPage={labelRowsPerPage}
          component="div"
          count={rows.length}
          rowsPerPage={rowsPerPage}
          page={page}
          backIconButtonProps={{
            'aria-label': 'previous page',
          }}
          nextIconButtonProps={{
            'aria-label': 'next page',
          }}
          onChangePage={handleChangePage}
          onChangeRowsPerPage={handleChangeRowsPerPage}
        />
      </Paper>
      <FormControlLabel
        control={<Switch checked={dense} onChange={handleChangeDense} />}
        label="Dense padding"
      />
    </div>
  );
};

export default React.memo(EnhancedTable);
