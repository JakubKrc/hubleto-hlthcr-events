import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import request from '@hubleto/react-ui/core/Request';
import { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import { useRecordField } from '@hubleto/react-ui/components/fc/FormRecordStore';

const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEvents';
const T = new Translator(parentApp + '/Loader', 'Components/InputAttendees');

interface AttendeeUser {
  id: number,
  first_name: string,
  last_name: string,
  ROLES?: Array<{role: string}>,
}

function parseAttendeeIds(value: any): Array<number> {
  let parsed = value;
  if (typeof value === 'string') {
    try {
      parsed = JSON.parse(value);
    } catch (error) {
      // An empty or invalid value means no attendees.
      parsed = [];
    }
  }
  if (!Array.isArray(parsed)) return [];

  const attendeeIds: Array<number> = [];
  for (const idUser of parsed) attendeeIds.push(Number(idUser));
  return attendeeIds;
}

function describeUser(user: AttendeeUser): string {
  const roleNames: Array<string> = [];
  for (const role of user.ROLES ?? []) roleNames.push(role.role);

  const fullName = (user.first_name ?? '') + ' ' + (user.last_name ?? '');
  return roleNames.length > 0 ? fullName + ' (' + roleNames.join(', ') + ')' : fullName;
}

/**
 * Attendees of an event as user checkboxes (legacy "Účastníci"), stored as a JSON list of user ids.
 * Only administrators and managers set attendees; for other users the server marks the field readonly.
 */
const InputAttendees = () => {
  const form = React.useContext(FormMetaContext);
  const attendeeIds = parseAttendeeIds(useRecordField('attendee_user_ids', '[]'));
  const isPublic = useRecordField('is_public', false);
  const [users, setUsers] = React.useState<Array<AttendeeUser>>([]);

  const isReadonly = form.description?.inputs?.attendee_user_ids?.readonly === true;
  const isEventForAll = isPublic === true || isPublic === 1 || isPublic === '1';

  function applyLoadedUsers(response: any) {
    const loadedUsers: Array<AttendeeUser> = [];
    for (const user of Object.values(response ?? {})) loadedUsers.push(user as AttendeeUser);
    setUsers(loadedUsers);
  }

  React.useEffect(() => {
    if (isReadonly) return;
    request.post('api/get-users', {}, {}, applyLoadedUsers);
  }, [isReadonly]);

  function toggleAttendee(idUser: number, isChecked: boolean) {
    const changedIds: Array<number> = [];
    for (const idAttendee of attendeeIds) {
      if (idAttendee !== idUser) changedIds.push(idAttendee);
    }
    if (isChecked) changedIds.push(idUser);
    form.changeRecord({attendee_user_ids: JSON.stringify(changedIds)});
  }

  // Legacy form: attendees are hidden for events "for all" and for users who cannot set them.
  if (isReadonly || isEventForAll) return null;

  return <div className='mt-4'>
    <div className='font-bold mb-1'>{T.translate('Attendees')}</div>
    {users.map((user) => <label key={user.id} className='flex gap-2 items-center justify-start w-fit cursor-pointer'>
      <input
        type='checkbox'
        checked={attendeeIds.includes(Number(user.id))}
        onChange={(event) => toggleAttendee(Number(user.id), event.target.checked)}
      />
      <span>{describeUser(user)}</span>
    </label>)}
  </div>;
}

export default InputAttendees;
