import {
    MdDrafts
} from 'react-icons/md';
import {
    FaDumpster,
    FaMailBulk
} from 'react-icons/fa';
import {
    TiArrowRightOutline,
    TiStarFullOutline
} from 'react-icons/ti';
import {
    RiInboxArchiveLine,
    RiSpam2Line,
    RiDeleteBinLine,
    RiShieldStarLine
} from 'react-icons/ri';
import {
    AiOutlineInbox
} from 'react-icons/ai';

export const folderMaps = {
    'inbox': AiOutlineInbox,
    'archive': RiInboxArchiveLine,
    'starred': TiStarFullOutline,
    'spam': RiSpam2Line,
    'drafts': MdDrafts,
    'junk': FaDumpster,
    'trash': RiDeleteBinLine,
    'sent': TiArrowRightOutline,
    'sent mail': TiArrowRightOutline,
    'important': RiShieldStarLine,
    'all mail': FaMailBulk
};
