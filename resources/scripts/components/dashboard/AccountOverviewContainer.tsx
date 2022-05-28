import tw from 'twin.macro';
import * as React from 'react';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import { useLocation } from 'react-router-dom';
import MessageBox from '@/components/MessageBox';
import ContentBox from '@/components/elements/ContentBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import UpdateUsernameForm from '@/components/dashboard/forms/UpdateUsernameForm';
import UpdateEmailAddressForm from '@/components/dashboard/forms/UpdateEmailAddressForm';

const Container = styled.div`
  ${tw`flex flex-wrap`};

  & > div {
    ${tw`w-full`};

    ${breakpoint('sm')`
      width: calc(50% - 1rem);
    `}

    ${breakpoint('md')`
      ${tw`w-auto flex-1`};
    `}
  }
`;

export default () => {
    const { state } = useLocation<undefined | { twoFactorRedirect?: boolean }>();

    return (
        <PageContentBlock title={'Account Overview'}>
            {state?.twoFactorRedirect &&
            <MessageBox title={'2-Factor Required'} type={'error'}>
                Your account must have two-factor authentication enabled in order to continue.
            </MessageBox>
            }
            <Container css={[ tw`lg:grid lg:grid-cols-2 mb-10`, state?.twoFactorRedirect ? tw`mt-4` : tw`mt-10` ]}>
                <ContentBox title={'Update Username'} showFlashes={'account:username'}>
                    <UpdateUsernameForm/>
                </ContentBox>
                <ContentBox
                    css={tw`mt-8 sm:mt-0 sm:ml-8`}
                    title={'Update Email Address'}
                    showFlashes={'account:email'}
                >
                    <UpdateEmailAddressForm/>
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
