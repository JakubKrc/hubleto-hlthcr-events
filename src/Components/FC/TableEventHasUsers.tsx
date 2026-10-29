import Table from '@hubleto/react-ui/components/fc/Table'
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces'
import FormEventHasUser from './FormEventHasUser'

interface TableEventHasUsersProps extends TableProps {
  idEvent?: number,
}

const componentName = 'TableEventHasUsers'
const parentApp = 'Hubleto/App/External/WaiBlue/HlthcrEvents'

const TableEventHasUsers = (props: TableEventHasUsersProps) => {
  const idEvent = props.idEvent ?? 0;
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/EventHasUser'}
    baseUrlSlug='hlthcr-events/event-has-users'
    endpointParams={{idEvent: idEvent}}
    formDefaultValues={idEvent > 0 ? {id_event: idEvent} : {}}
    formModalProps={{type: 'right'}}
    renderForm={(table: TableMeta) => <FormEventHasUser {...table.getDefaultFormProps()} />}
    {...props}
  />;
}

export default TableEventHasUsers;
