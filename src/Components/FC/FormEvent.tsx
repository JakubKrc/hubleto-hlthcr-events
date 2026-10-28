import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import { useRecordField } from '@hubleto/react-ui/components/fc/FormRecordStore';
import InputAttendees from './InputAttendees';

const componentName = 'FormEvent';
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEvents';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

// Legacy: users other than administrators and managers cannot change events "for all".
const PublicEventNotice = () => {
  const form = React.useContext(FormMetaContext);
  const isPublic = useRecordField('is_public', false);
  const isEventForAll = isPublic === true || isPublic === 1 || isPublic === '1';
  const canChangePublicEvents = form.description?.inputs?.is_public?.readonly !== true;

  if (!isEventForAll || canChangePublicEvents || form.id <= 0) return null;
  return <div className='alert alert-info'>
    {T.translate('Only administrators and managers can change events for all.')}
  </div>;
}

const TabDefault = () => <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
  <div>
    <PublicEventNotice />
    <Input field='name' />
    <div className='grid grid-cols-2 gap-2'>
      <Input field='date_start' />
      <Input field='time_start' />
      <Input field='date_end' />
      <Input field='time_end' />
    </div>
    <Input field='is_all_day' />
    <Input field='note' />
  </div>
  <div>
    <Input field='id_user' />
    <Input field='id_car' />
    <Input field='is_executed' />
    <Input field='is_public' />
    <InputAttendees />
  </div>
</div>;

const FormEvent = (props: FormProps) => <Form
  componentName={componentName}
  parentApp={parentApp}
  model={parentApp + '/Models/Event'}
  urlSlug='hlthcr-events/events'
  title={{field: 'name', sub: T.translate('Event')}}
  tabs={{default: {content: () => <TabDefault />}}}
  {...props}
/>;

export default FormEvent;
