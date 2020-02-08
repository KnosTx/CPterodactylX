import React, { useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import loginCheckpoint from '@/api/auth/loginCheckpoint';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Actions, useStoreActions } from 'easy-peasy';
import { StaticContext } from 'react-router';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import Spinner from '@/components/elements/Spinner';
import styled from 'styled-components';
import { breakpoint } from 'styled-components-breakpoint';

const Container = styled.div`
    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 660px;
    `};
`;

export default ({ history, location: { state } }: RouteComponentProps<{}, StaticContext, { token?: string }>) => {
    const [ code, setCode ] = useState('');
    const [ isLoading, setIsLoading ] = useState(false);

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    if (!state || !state.token) {
        history.replace('/auth/login');

        return null;
    }

    const onChangeHandler = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.value.length <= 6) {
            setCode(e.target.value);
        }
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        setIsLoading(true);
        clearFlashes();

        loginCheckpoint(state.token!, code)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                }
            })
            .catch(error => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
                setIsLoading(false);
            });
    };

    return (
        <React.Fragment>
            <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                Device Checkpoint
            </h2>
            <Container>
                <FlashMessageRender/>
                <LoginFormContainer onSubmit={submit}>
                    <div className={'mt-6'}>
                        <label htmlFor={'authentication_code'}>Authentication Code</label>
                        <input
                            id={'authentication_code'}
                            type={'number'}
                            autoFocus={true}
                            className={'input'}
                            value={code}
                            onChange={onChangeHandler}
                        />
                    </div>
                    <div className={'mt-6'}>
                        <button
                            type={'submit'}
                            className={'btn btn-primary btn-jumbo'}
                            disabled={isLoading || code.length !== 6}
                        >
                            {isLoading ?
                                <Spinner size={'tiny'} className={'mx-auto'}/>
                                :
                                'Continue'
                            }
                        </button>
                    </div>
                    <div className={'mt-6 text-center'}>
                        <Link
                            to={'/auth/login'}
                            className={'text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700'}
                        >
                            Return to Login
                        </Link>
                    </div>
                </LoginFormContainer>
            </Container>
        </React.Fragment>
    );
};
