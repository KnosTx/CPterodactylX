import React from 'react';
import tw from 'twin.macro';
import { useFormikContext } from 'formik';
import AdminBox from '@/components/admin/AdminBox';
import { faCogs } from '@fortawesome/free-solid-svg-icons';
import Field from '@/components/elements/Field';
import OwnerSelect from '@/components/admin/servers/OwnerSelect';
import getServerDetails from '@/api/swr/admin/getServerDetails';

export default () => {
    const { data: server } = getServerDetails();
    const { isSubmitting } = useFormikContext();

    if (!server) return null;

    return (
        <AdminBox icon={faCogs} title={'Settings'} isLoading={isSubmitting}>
            <div css={tw`grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6`}>
                <Field id={'name'} name={'name'} label={'Server Name'} type={'text'}/>
                <Field id={'externalId'} name={'externalId'} label={'External Identifier'} type={'text'}/>
                <OwnerSelect selected={server.relations.user || null}/>
            </div>
        </AdminBox>
    );
};
