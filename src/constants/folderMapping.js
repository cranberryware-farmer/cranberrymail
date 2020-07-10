import {
  MdDrafts,
  MdInbox,
  MdArchive,
  MdError,
  MdStar,
  MdSend,
  MdDelete
} from 'react-icons/md';
import {
  FaDumpster,
  FaMailBulk
} from 'react-icons/fa';
import {
  RiShieldStarLine
} from 'react-icons/ri';

export const folderMaps = {
  'inbox': MdInbox,
  'archive': MdArchive,
  'starred': MdStar,
  'spam': MdError,
  'drafts': MdDrafts,
  'junk': FaDumpster,
  'trash': MdDelete,
  'sent': MdSend,
  'sent mail': MdSend,
  'important': RiShieldStarLine,
  'all mail': FaMailBulk
};
